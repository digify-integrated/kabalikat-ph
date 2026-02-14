<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_app_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_app_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_app_update
            AFTER UPDATE ON app
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'App changed.<br/><br/>';

                IF NEW.app_name <> OLD.app_name THEN
                    SET audit_log = CONCAT(audit_log, "App Name: ", OLD.app_name, " -> ", NEW.app_name, "<br/>");
                END IF;

                IF NEW.app_description <> OLD.app_description THEN
                    SET audit_log = CONCAT(audit_log, "App Description: ", OLD.app_description, " -> ", NEW.app_description, "<br/>");
                END IF;

                IF NEW.navigation_menu_name <> OLD.navigation_menu_name THEN
                    SET audit_log = CONCAT(audit_log, "Navigation Menu: ", OLD.navigation_menu_name, " -> ", NEW.navigation_menu_name, "<br/>");
                END IF;

                IF NEW.order_sequence <> OLD.order_sequence THEN
                    SET audit_log = CONCAT(audit_log, "Order Sequence: ", OLD.order_sequence, " -> ", NEW.order_sequence, "<br/>");
                END IF;
                
                IF audit_log <> 'App changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('app', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_app_insert
            AFTER INSERT ON app
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'App created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('app', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_app_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_app_insert');
    }
};
