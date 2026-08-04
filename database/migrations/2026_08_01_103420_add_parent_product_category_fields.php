<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_category', function (Blueprint $table) {
            $table->integer('parent_id')->nullable()->after('product_category_name');
            $table->string('parent_name')->nullable()->after('parent_id');
        });

        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_category_update');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_product_category_update
            AFTER UPDATE ON product_category
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Product category changed.<br/><br/>';

                IF NEW.product_category_name <> OLD.product_category_name THEN
                    SET audit_log = CONCAT(audit_log, "Product Category: ", OLD.product_category_name, " -> ", NEW.product_category_name, "<br/>");
                END IF;

                IF NEW.parent_name <> OLD.parent_name THEN
                    SET audit_log = CONCAT(audit_log, "Parent Category: ", OLD.parent_name, " -> ", NEW.parent_name, "<br/>");
                END IF;
                
                IF audit_log <> 'Product category changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('product_category', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);
    }

    public function down(): void
    {
        Schema::table('product_category', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'parent_name']);
        });
    }
};
