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
        /* =============================================================================================
            TABLE: APP
        ============================================================================================= */

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

        /* =============================================================================================
            TABLE: NAVIGATION MENU
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_navigation_menu_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_navigation_menu_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_navigation_menu_update
            AFTER UPDATE ON navigation_menu
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Navigation menu changed.<br/><br/>';

                IF NEW.navigation_menu_name <> OLD.navigation_menu_name THEN
                    SET audit_log = CONCAT(audit_log, "Navigation Menu Name: ", OLD.navigation_menu_name, " -> ", NEW.navigation_menu_name, "<br/>");
                END IF;

                IF NEW.navigation_menu_icon <> OLD.navigation_menu_icon THEN
                    SET audit_log = CONCAT(audit_log, "Navigation Menu Icon: ", OLD.navigation_menu_icon, " -> ", NEW.navigation_menu_icon, "<br/>");
                END IF;

                IF NEW.app_name <> OLD.app_name THEN
                    SET audit_log = CONCAT(audit_log, "App Name: ", OLD.app_name, " -> ", NEW.app_name, "<br/>");
                END IF;

                IF NEW.parent_navigation_menu_name <> OLD.parent_navigation_menu_name THEN
                    SET audit_log = CONCAT(audit_log, "Parent Navigation Menu: ", OLD.parent_navigation_menu_name, " -> ", NEW.parent_navigation_menu_name, "<br/>");
                END IF;

                IF NEW.database_table <> OLD.database_table THEN
                    SET audit_log = CONCAT(audit_log, "Database Table: ", OLD.database_table, " -> ", NEW.database_table, "<br/>");
                END IF;

                IF NEW.order_sequence <> OLD.order_sequence THEN
                    SET audit_log = CONCAT(audit_log, "Order Sequence: ", OLD.order_sequence, " -> ", NEW.order_sequence, "<br/>");
                END IF;
                
                IF audit_log <> 'Navigation menu changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('navigation_menu', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_navigation_menu_insert
            AFTER INSERT ON navigation_menu
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Navigation menu created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('navigation_menu', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: NAVIGATION MENU ROUTE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_navigation_menu_route_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_navigation_menu_route_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_navigation_menu_route_update
            AFTER UPDATE ON navigation_menu_route
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Navigation menu route changed.<br/><br/>';

                IF NEW.route_type <> OLD.route_type THEN
                    SET audit_log = CONCAT(audit_log, "Route Type: ", OLD.route_type, " -> ", NEW.route_type, "<br/>");
                END IF;

                IF NEW.view_file <> OLD.view_file THEN
                    SET audit_log = CONCAT(audit_log, "View File: ", OLD.view_file, " -> ", NEW.view_file, "<br/>");
                END IF;

                IF NEW.js_file <> OLD.js_file THEN
                    SET audit_log = CONCAT(audit_log, "Js File: ", OLD.js_file, " -> ", NEW.js_file, "<br/>");
                END IF;
                
                IF audit_log <> 'Navigation menu route changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('navigation_menu_route', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_navigation_menu_route_insert
            AFTER INSERT ON navigation_menu_route
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Navigation menu route created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('navigation_menu_route', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: SYSTEM ACTION
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_system_action_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_system_action_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_system_action_update
            AFTER UPDATE ON system_action
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'System action changed.<br/><br/>';

                IF NEW.system_action_name <> OLD.system_action_name THEN
                    SET audit_log = CONCAT(audit_log, "System Action Name: ", OLD.system_action_name, " -> ", NEW.system_action_name, "<br/>");
                END IF;

                IF NEW.system_action_description <> OLD.system_action_description THEN
                    SET audit_log = CONCAT(audit_log, "System Action Description: ", OLD.system_action_description, " -> ", NEW.system_action_description, "<br/>");
                END IF;
                
                IF audit_log <> 'System action changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('system_action', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_system_action_insert
            AFTER INSERT ON system_action
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'System action created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('system_action', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: ROLE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_role_update
            AFTER UPDATE ON role
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Role changed.<br/><br/>';

                IF NEW.role_name <> OLD.role_name THEN
                    SET audit_log = CONCAT(audit_log, "Role Name: ", OLD.role_name, " -> ", NEW.role_name, "<br/>");
                END IF;

                IF NEW.role_description <> OLD.role_description THEN
                    SET audit_log = CONCAT(audit_log, "Role Description: ", OLD.role_description, " -> ", NEW.role_description, "<br/>");
                END IF;
                
                IF audit_log <> 'Role changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('role', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_role_insert
            AFTER INSERT ON role
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Role created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('role', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: ROLE PERMISSION
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_permission_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_permission_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_role_permission_update
            AFTER UPDATE ON role_permission
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Role permission changed.<br/><br/>';

                IF NEW.role_name <> OLD.role_name THEN
                    SET audit_log = CONCAT(audit_log, "Role: ", OLD.role_name, " -> ", NEW.role_name, "<br/>");
                END IF;

                IF NEW.navigation_menu_name <> OLD.navigation_menu_name THEN
                    SET audit_log = CONCAT(audit_log, "Navigation Menu: ", OLD.navigation_menu_name, " -> ", NEW.navigation_menu_name, "<br/>");
                END IF;

                IF NEW.read_access <> OLD.read_access THEN
                    SET audit_log = CONCAT(audit_log, "Read Access: ", OLD.read_access, " -> ", NEW.read_access, "<br/>");
                END IF;

                IF NEW.write_access <> OLD.write_access THEN
                    SET audit_log = CONCAT(audit_log, "Write Access: ", OLD.write_access, " -> ", NEW.write_access, "<br/>");
                END IF;

                IF NEW.create_access <> OLD.create_access THEN
                    SET audit_log = CONCAT(audit_log, "Create Access: ", OLD.create_access, " -> ", NEW.create_access, "<br/>");
                END IF;

                IF NEW.import_access <> OLD.import_access THEN
                    SET audit_log = CONCAT(audit_log, "Import Access: ", OLD.import_access, " -> ", NEW.import_access, "<br/>");
                END IF;

                IF NEW.export_access <> OLD.export_access THEN
                    SET audit_log = CONCAT(audit_log, "Export Access: ", OLD.export_access, " -> ", NEW.export_access, "<br/>");
                END IF;

                IF NEW.logs_access <> OLD.logs_access THEN
                    SET audit_log = CONCAT(audit_log, "Logs Access: ", OLD.logs_access, " -> ", NEW.logs_access, "<br/>");
                END IF;
                
                IF audit_log <> 'Role permission changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('role_permission', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_role_permission_insert
            AFTER INSERT ON role_permission
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Role permission created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('role_permission', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: ROLE SYSTEM ACTION PERMISSION
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_system_action_permission_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_system_action_permission_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_role_system_action_permission_update
            AFTER UPDATE ON role_system_action_permission
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Role system action changed.<br/><br/>';

                IF NEW.role_name <> OLD.role_name THEN
                    SET audit_log = CONCAT(audit_log, "Role: ", OLD.role_name, " -> ", NEW.role_name, "<br/>");
                END IF;

                IF NEW.system_action_name <> OLD.system_action_name THEN
                    SET audit_log = CONCAT(audit_log, "System Action: ", OLD.system_action_name, " -> ", NEW.system_action_name, "<br/>");
                END IF;

                IF NEW.system_action_access <> OLD.system_action_access THEN
                    SET audit_log = CONCAT(audit_log, "System Action Access: ", OLD.system_action_access, " -> ", NEW.system_action_access, "<br/>");
                END IF;
                
                IF audit_log <> 'Role system action permission changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('role_system_action_permission', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_role_system_action_permission_insert
            AFTER INSERT ON role_system_action_permission
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Role system action permission created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('role_system_action_permission', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: ROLE USER ACCOUNT
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_user_account_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_user_account_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_role_user_account_update
            AFTER UPDATE ON role_user_account
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Role user account changed.<br/><br/>';

                IF NEW.role_name <> OLD.role_name THEN
                    SET audit_log = CONCAT(audit_log, "Role: ", OLD.role_name, " -> ", NEW.role_name, "<br/>");
                END IF;

                IF NEW.user_name <> OLD.user_name THEN
                    SET audit_log = CONCAT(audit_log, "User: ", OLD.user_name, " -> ", NEW.user_name, "<br/>");
                END IF;
                
                IF audit_log <> 'Role user account changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('role_user_account', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_role_user_account_insert
            AFTER INSERT ON role_user_account
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Role user account created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('role_user_account', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: FILE TYPE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_file_type_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_file_type_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_file_type_update
            AFTER UPDATE ON file_type
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'File type changed.<br/><br/>';

                IF NEW.file_type_name <> OLD.file_type_name THEN
                    SET audit_log = CONCAT(audit_log, "File Type Name: ", OLD.file_type_name, " -> ", NEW.file_type_name, "<br/>");
                END IF;
                
                IF audit_log <> 'File type changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('file_type', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_file_type_insert
            AFTER INSERT ON file_type
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'File type created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('file_type', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: FILE EXTENSION
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_file_extension_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_file_extension_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_file_extension_update
            AFTER UPDATE ON file_extension
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'File extension changed.<br/><br/>';

                IF NEW.file_extension_name <> OLD.file_extension_name THEN
                    SET audit_log = CONCAT(audit_log, "File Extension Name: ", OLD.file_extension_name, " -> ", NEW.file_extension_name, "<br/>");
                END IF;

                IF NEW.file_extension <> OLD.file_extension THEN
                    SET audit_log = CONCAT(audit_log, "File Extension: ", OLD.file_extension, " -> ", NEW.file_extension, "<br/>");
                END IF;

                IF NEW.file_type_name <> OLD.file_type_name THEN
                    SET audit_log = CONCAT(audit_log, "File Type: ", OLD.file_type_name, " -> ", NEW.file_type_name, "<br/>");
                END IF;
                
                IF audit_log <> 'File extension changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('file_extension', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_file_extension_insert
            AFTER INSERT ON file_extension
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'File extension created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('file_extension', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: UPLOAD SETTING
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_upload_setting_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_upload_setting_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_upload_setting_update
            AFTER UPDATE ON upload_setting
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Upload setting changed.<br/><br/>';

                IF NEW.upload_setting_name <> OLD.upload_setting_name THEN
                    SET audit_log = CONCAT(audit_log, "Upload Setting Name: ", OLD.upload_setting_name, " -> ", NEW.upload_setting_name, "<br/>");
                END IF;

                IF NEW.upload_setting_description <> OLD.upload_setting_description THEN
                    SET audit_log = CONCAT(audit_log, "Upload Setting Description: ", OLD.upload_setting_description, " -> ", NEW.upload_setting_description, "<br/>");
                END IF;

                IF NEW.max_file_size <> OLD.max_file_size THEN
                    SET audit_log = CONCAT(audit_log, "Max File Size: ", OLD.max_file_size, " -> ", NEW.max_file_size, "<br/>");
                END IF;
                
                IF audit_log <> 'Upload setting changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('upload_setting', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_upload_setting_insert
            AFTER INSERT ON upload_setting
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Upload setting created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('upload_setting', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: UPLOAD SETTING FILE EXTENSION
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_upload_setting_file_extension_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_upload_setting_file_extension_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_upload_setting_file_extension_update
            AFTER UPDATE ON upload_setting_file_extension
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Upload setting file extension changed.<br/><br/>';

                IF NEW.upload_setting_name <> OLD.upload_setting_name THEN
                    SET audit_log = CONCAT(audit_log, "Upload Setting: ", OLD.upload_setting_name, " -> ", NEW.upload_setting_name, "<br/>");
                END IF;

                IF NEW.file_extension_name <> OLD.file_extension_name THEN
                    SET audit_log = CONCAT(audit_log, "File Extension Name: ", OLD.file_extension_name, " -> ", NEW.file_extension_name, "<br/>");
                END IF;

                IF NEW.file_extension <> OLD.file_extension THEN
                    SET audit_log = CONCAT(audit_log, "File Extension: ", OLD.file_extension, " -> ", NEW.file_extension, "<br/>");
                END IF;
                
                IF audit_log <> 'Upload setting file extension changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('upload_setting_file_extension', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_upload_setting_file_extension_insert
            AFTER INSERT ON upload_setting_file_extension
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Upload setting file extension created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('upload_setting_file_extension', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /* =============================================================================================
            TABLE: APP
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_app_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_app_insert');

        /* =============================================================================================
            TABLE: NAVIGATION MENU
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_navigation_menu_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_navigation_menu_insert');

        /* =============================================================================================
            TABLE: NAVIGATION MENU ROUTE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_navigation_menu_route_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_navigation_menu_route_insert');
        
        /* =============================================================================================
            TABLE: SYSTEM ACTION
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_system_action_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_system_action_insert');
        
        /* =============================================================================================
            TABLE: ROLE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_insert');
        
        /* =============================================================================================
            TABLE: ROLE PERMISSION
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_permission_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_permission_insert');

        /* =============================================================================================
            TABLE: ROLE SYSTEM ACTION PERMISSION
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_system_action_permission_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_system_action_permission_insert');

        /* =============================================================================================
            TABLE: ROLE USER ACCOUNT
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_user_account_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_role_user_account_insert');

        /* =============================================================================================
            TABLE: FILE TYPE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_file_type_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_file_type_insert');

        /* =============================================================================================
            TABLE: FILE EXTENSION
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_file_extension_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_file_extension_insert');
        
        /* =============================================================================================
            TABLE: UPLOAD SETTING
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_upload_setting_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_upload_setting_insert');
        
        /* =============================================================================================
            TABLE: UPLOAD SETTING FILE EXTENSION
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_upload_setting_file_extension_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_upload_setting_file_extension_insert');
    }
};
