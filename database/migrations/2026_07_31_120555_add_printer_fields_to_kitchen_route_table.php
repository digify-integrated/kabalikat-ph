<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchen_route', function (Blueprint $table) {
            $table->string('printer_ip')->nullable()->after('kitchen_route_name');
            $table->integer('printer_port')->default(9100)->after('printer_ip');
        });

        DB::unprepared('DROP TRIGGER IF EXISTS trg_kitchen_route_update');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_kitchen_route_update
            AFTER UPDATE ON kitchen_route
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Kitchen route changed.<br/><br/>';

                IF NEW.kitchen_route_name <> OLD.kitchen_route_name THEN
                    SET audit_log = CONCAT(audit_log, "Kitchen Route:", OLD.kitchen_route_name, " -> ", NEW.kitchen_route_name, "<br/>");
                END IF;

                IF NEW.printer_ip <> OLD.printer_ip THEN
                    SET audit_log = CONCAT(audit_log, "Printer IP:", OLD.printer_ip, " -> ", NEW.printer_ip, "<br/>");
                END IF;

                IF NEW.printer_port <> OLD.printer_port THEN
                    SET audit_log = CONCAT(audit_log, "Printer Port:", OLD.printer_port, " -> ", NEW.printer_port, "<br/>");
                END IF;
                
                IF audit_log <> 'Kitchen route changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('kitchen_route', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);
    }

    public function down(): void
    {
        Schema::table('kitchen_route', function (Blueprint $table) {
            $table->dropColumn(['printer_ip', 'printer_port']);
        });
    }
};
