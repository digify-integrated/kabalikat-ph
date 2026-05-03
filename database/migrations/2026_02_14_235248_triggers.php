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

        DB::unprepared('DROP TRIGGER IF EXISTS trg_users_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_users_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_app_update
            AFTER UPDATE ON users
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'User changed.<br/><br/>';

                IF NEW.name <> OLD.name THEN
                    SET audit_log = CONCAT(audit_log, "Name: ", OLD.name, " -> ", NEW.name, "<br/>");
                END IF;

                IF NEW.email <> OLD.email THEN
                    SET audit_log = CONCAT(audit_log, "Email: ", OLD.email, " -> ", NEW.email, "<br/>");
                END IF;

                IF NEW.status <> OLD.status THEN
                    SET audit_log = CONCAT(audit_log, "Status: ", OLD.status, " -> ", NEW.status, "<br/>");
                END IF;
                
                IF audit_log <> 'User changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('users', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_app_insert
            AFTER INSERT ON users
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'User created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('users', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

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

        /* =============================================================================================
            TABLE: COUNTRY
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_country_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_country_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_country_update
            AFTER UPDATE ON country
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Country changed.<br/><br/>';

                IF NEW.country_name <> OLD.country_name THEN
                    SET audit_log = CONCAT(audit_log, "Country: ", OLD.country_name, " -> ", NEW.country_name, "<br/>");
                END IF;
                
                IF audit_log <> 'Country changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('country', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_country_insert
            AFTER INSERT ON country
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Country created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('country', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: STATE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_state_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_state_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_state_update
            AFTER UPDATE ON state
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'State changed.<br/><br/>';

                IF NEW.state_name <> OLD.state_name THEN
                    SET audit_log = CONCAT(audit_log, "State: ", OLD.state_name, " -> ", NEW.state_name, "<br/>");
                END IF;

                IF NEW.country_name <> OLD.country_name THEN
                    SET audit_log = CONCAT(audit_log, "Country: ", OLD.country_name, " -> ", NEW.country_name, "<br/>");
                END IF;
                
                IF audit_log <> 'State changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('state', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_state_insert
            AFTER INSERT ON state
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'State created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('state', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: CITY
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_city_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_city_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_city_update
            AFTER UPDATE ON city
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'City changed.<br/><br/>';

                IF NEW.city_name <> OLD.city_name THEN
                    SET audit_log = CONCAT(audit_log, "City: ", OLD.city_name, " -> ", NEW.city_name, "<br/>");
                END IF;

                IF NEW.state_name <> OLD.state_name THEN
                    SET audit_log = CONCAT(audit_log, "State: ", OLD.state_name, " -> ", NEW.state_name, "<br/>");
                END IF;

                IF NEW.country_name <> OLD.country_name THEN
                    SET audit_log = CONCAT(audit_log, "Country: ", OLD.country_name, " -> ", NEW.country_name, "<br/>");
                END IF;
                
                IF audit_log <> 'City changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('city', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_city_insert
            AFTER INSERT ON city
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'City created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('city', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: CURRENCY
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_currency_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_currency_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_currency_update
            AFTER UPDATE ON currency
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Currency changed.<br/><br/>';

                IF NEW.currency_name <> OLD.currency_name THEN
                    SET audit_log = CONCAT(audit_log, "Currency: ", OLD.currency_name, " -> ", NEW.currency_name, "<br/>");
                END IF;

                IF NEW.symbol <> OLD.symbol THEN
                    SET audit_log = CONCAT(audit_log, "Currency: ", OLD.symbol, " -> ", NEW.symbol, "<br/>");
                END IF;

                IF NEW.shorthand <> OLD.shorthand THEN
                    SET audit_log = CONCAT(audit_log, "Shorthand: ", OLD.shorthand, " -> ", NEW.shorthand, "<br/>");
                END IF;
                
                IF audit_log <> 'Currency changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('currency', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_currency_insert
            AFTER INSERT ON currency
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Currency created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('currency', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: COMPANY
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_company_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_company_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_company_update
            AFTER UPDATE ON company
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Company changed.<br/><br/>';

                IF NEW.company_name <> OLD.company_name THEN
                    SET audit_log = CONCAT(audit_log, "Company: ", OLD.company_name, " -> ", NEW.company_name, "<br/>");
                END IF;

                IF NEW.address <> OLD.address THEN
                    SET audit_log = CONCAT(audit_log, "Address: ", OLD.address, " -> ", NEW.address, "<br/>");
                END IF;

                IF NEW.city_name <> OLD.city_name THEN
                    SET audit_log = CONCAT(audit_log, "City: ", OLD.city_name, " -> ", NEW.city_name, "<br/>");
                END IF;

                IF NEW.state_name <> OLD.state_name THEN
                    SET audit_log = CONCAT(audit_log, "State: ", OLD.state_name, " -> ", NEW.state_name, "<br/>");
                END IF;

                IF NEW.country_name <> OLD.country_name THEN
                    SET audit_log = CONCAT(audit_log, "Country: ", OLD.country_name, " -> ", NEW.country_name, "<br/>");
                END IF;

                IF NEW.tax_id <> OLD.tax_id THEN
                    SET audit_log = CONCAT(audit_log, "Tax Id: ", OLD.tax_id, " -> ", NEW.tax_id, "<br/>");
                END IF;

                IF NEW.currency_name <> OLD.currency_name THEN
                    SET audit_log = CONCAT(audit_log, "Currency: ", OLD.currency_name, " -> ", NEW.currency_name, "<br/>");
                END IF;

                IF NEW.phone <> OLD.phone THEN
                    SET audit_log = CONCAT(audit_log, "Phone: ", OLD.phone, " -> ", NEW.phone, "<br/>");
                END IF;

                IF NEW.telephone <> OLD.telephone THEN
                    SET audit_log = CONCAT(audit_log, "Telephone: ", OLD.telephone, " -> ", NEW.telephone, "<br/>");
                END IF;

                IF NEW.email <> OLD.email THEN
                    SET audit_log = CONCAT(audit_log, "Email: ", OLD.email, " -> ", NEW.email, "<br/>");
                END IF;

                IF NEW.website <> OLD.website THEN
                    SET audit_log = CONCAT(audit_log, "Website: ", OLD.website, " -> ", NEW.website, "<br/>");
                END IF;
                
                IF audit_log <> 'Company changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('company', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_company_insert
            AFTER INSERT ON company
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Company created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('company', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: ATTRIBUTE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_attribute_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_attribute_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_attribute_update
            AFTER UPDATE ON attribute
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Attribute changed.<br/><br/>';

                IF NEW.attribute_name <> OLD.attribute_name THEN
                    SET audit_log = CONCAT(audit_log, "Attribute: ", OLD.attribute_name, " -> ", NEW.attribute_name, "<br/>");
                END IF;

                IF NEW.selection_type <> OLD.selection_type THEN
                    SET audit_log = CONCAT(audit_log, "Selection Type: ", OLD.selection_type, " -> ", NEW.selection_type, "<br/>");
                END IF;
                
                IF audit_log <> 'Attribute changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('attribute', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_attribute_insert
            AFTER INSERT ON attribute
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Attribute created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('attribute', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: ATTRIBUTE VALUE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_attribute_value_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_attribute_value_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_attribute_value_update
            AFTER UPDATE ON attribute_value
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Attribute value changed.<br/><br/>';

                IF NEW.attribute_value <> OLD.attribute_value THEN
                    SET audit_log = CONCAT(audit_log, "Attribute Value: ", OLD.attribute_value, " -> ", NEW.attribute_value, "<br/>");
                END IF;

                IF NEW.attribute_name <> OLD.attribute_name THEN
                    SET audit_log = CONCAT(audit_log, "Attribute: ", OLD.attribute_name, " -> ", NEW.attribute_name, "<br/>");
                END IF;
                
                IF audit_log <> 'Attribute value changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('attribute_value', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_attribute_value_insert
            AFTER INSERT ON attribute_value
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Attribute value created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('attribute_value', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: PRODUCT CATEGORY
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_category_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_category_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_product_category_update
            AFTER UPDATE ON product_category
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Product category changed.<br/><br/>';

                IF NEW.product_category_name <> OLD.product_category_name THEN
                    SET audit_log = CONCAT(audit_log, "Product Category: ", OLD.product_category_name, " -> ", NEW.product_category_name, "<br/>");
                END IF;
                
                IF audit_log <> 'Product category changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('product_category', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_product_category_insert
            AFTER INSERT ON product_category
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Product category created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('product_category', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: STOCK ADJUSTMENT REASON 
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_adjustment_reason_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_adjustment_reason_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_adjustment_reason_update
            AFTER UPDATE ON stock_adjustment_reason
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock adjustment reason changed.<br/><br/>';

                IF NEW.stock_adjustment_reason_name <> OLD.stock_adjustment_reason_name THEN
                    SET audit_log = CONCAT(audit_log, "Stock Adjustment Reason: ", OLD.stock_adjustment_reason_name, " -> ", NEW.stock_adjustment_reason_name, "<br/>");
                END IF;
                
                IF audit_log <> 'Stock adjustment reason changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('stock_adjustment_reason', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_adjustment_reason_insert
            AFTER INSERT ON stock_adjustment_reason
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock adjustment reason created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('stock_adjustment_reason', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: STOCK TRANSFER REASON 
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_transfer_reason_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_transfer_reason_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_transfer_reason_update
            AFTER UPDATE ON stock_transfer_reason
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock transfer reason changed.<br/><br/>';

                IF NEW.stock_transfer_reason_name <> OLD.stock_transfer_reason_name THEN
                    SET audit_log = CONCAT(audit_log, "Stock Transfer Reason: ", OLD.stock_transfer_reason_name, " -> ", NEW.stock_transfer_reason_name, "<br/>");
                END IF;
                
                IF audit_log <> 'Stock transfer reason changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('stock_transfer_reason', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_transfer_reason_insert
            AFTER INSERT ON stock_transfer_reason
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock transfer reason created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('stock_transfer_reason', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: SUPPLIER
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_supplier_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_supplier_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_supplier_update
            AFTER UPDATE ON supplier
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Supplier changed.<br/><br/>';

                IF NEW.supplier_name <> OLD.supplier_name THEN
                    SET audit_log = CONCAT(audit_log, "Supplier: ", OLD.supplier_name, " -> ", NEW.supplier_name, "<br/>");
                END IF;

                IF NEW.contact_person <> OLD.contact_person THEN
                    SET audit_log = CONCAT(audit_log, "Contact Person: ", OLD.contact_person, " -> ", NEW.contact_person, "<br/>");
                END IF;

                IF NEW.supplier_status <> OLD.supplier_status THEN
                    SET audit_log = CONCAT(audit_log, "Supplier Status: ", OLD.supplier_status, " -> ", NEW.supplier_status, "<br/>");
                END IF;

                IF NEW.address <> OLD.address THEN
                    SET audit_log = CONCAT(audit_log, "Address: ", OLD.address, " -> ", NEW.address, "<br/>");
                END IF;

                IF NEW.city_name <> OLD.city_name THEN
                    SET audit_log = CONCAT(audit_log, "City: ", OLD.city_name, " -> ", NEW.city_name, "<br/>");
                END IF;

                IF NEW.state_name <> OLD.state_name THEN
                    SET audit_log = CONCAT(audit_log, "State: ", OLD.state_name, " -> ", NEW.state_name, "<br/>");
                END IF;

                IF NEW.country_name <> OLD.country_name THEN
                    SET audit_log = CONCAT(audit_log, "Country: ", OLD.country_name, " -> ", NEW.country_name, "<br/>");
                END IF;

                IF NEW.phone <> OLD.phone THEN
                    SET audit_log = CONCAT(audit_log, "Phone:", OLD.phone, " -> ", NEW.phone, "<br/>");
                END IF;

                IF NEW.telephone <> OLD.telephone THEN
                    SET audit_log = CONCAT(audit_log, "Telephone:", OLD.telephone, " -> ", NEW.telephone, "<br/>");
                END IF;

                IF NEW.email <> OLD.email THEN
                    SET audit_log = CONCAT(audit_log, "Email:", OLD.email, " -> ", NEW.email, "<br/>");
                END IF;
                
                IF audit_log <> 'Supplier changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('supplier', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_supplier_insert
            AFTER INSERT ON supplier
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Supplier created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('supplier', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: UNIT TYPE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_unit_type_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_unit_type_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_unit_type_update
            AFTER UPDATE ON unit_type
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Unit type changed.<br/><br/>';

                IF NEW.unit_type_name <> OLD.unit_type_name THEN
                    SET audit_log = CONCAT(audit_log, "Unit Type: ", OLD.unit_type_name, " -> ", NEW.unit_type_name, "<br/>");
                END IF;
                
                IF audit_log <> 'Unit type changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('unit_type', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_unit_type_insert
            AFTER INSERT ON unit_type
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Unit type created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('unit_type', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: UNIT
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_unit_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_unit_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_unit_update
            AFTER UPDATE ON unit
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Unit changed.<br/><br/>';

                IF NEW.unit_name <> OLD.unit_name THEN
                    SET audit_log = CONCAT(audit_log, "Unit: ", OLD.unit_name, " -> ", NEW.unit_name, "<br/>");
                END IF;

                IF NEW.abbreviation <> OLD.abbreviation THEN
                    SET audit_log = CONCAT(audit_log, "Abbreviation: ", OLD.abbreviation, " -> ", NEW.abbreviation, "<br/>");
                END IF;

                IF NEW.unit_type_name <> OLD.unit_type_name THEN
                    SET audit_log = CONCAT(audit_log, "Unit Type: ", OLD.unit_type_name, " -> ", NEW.unit_type_name, "<br/>");
                END IF;
                
                IF audit_log <> 'Unit changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('unit', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_unit_insert
            AFTER INSERT ON unit
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Unit created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('unit', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: UNIT CONVERSION
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_unit_conversion_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_unit_conversion_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_unit_conversion_update
            AFTER UPDATE ON unit_conversion
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Unit changed.<br/><br/>';

                IF NEW.from_unit_name <> OLD.from_unit_name THEN
                    SET audit_log = CONCAT(audit_log, "From: ", OLD.from_unit_name, " -> ", NEW.from_unit_name, "<br/>");
                END IF;

                IF NEW.to_unit_name <> OLD.to_unit_name THEN
                    SET audit_log = CONCAT(audit_log, "To: ", OLD.to_unit_name, " -> ", NEW.to_unit_name, "<br/>");
                END IF;

                IF NEW.conversion_factor <> OLD.conversion_factor THEN
                    SET audit_log = CONCAT(audit_log, "Conversion Factor: ", OLD.conversion_factor, " -> ", NEW.conversion_factor, "<br/>");
                END IF;
                
                IF audit_log <> 'Unit conversion changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('unit_conversion', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_unit_conversion_insert
            AFTER INSERT ON unit_conversion
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Unit conversion created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('unit_conversion', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);
        
        /* =============================================================================================
            TABLE: WAREHOUSE TYPE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_warehouse_type_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_warehouse_type_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_warehouse_type_update
            AFTER UPDATE ON warehouse_type
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Warehouse type changed.<br/><br/>';

                IF NEW.warehouse_type_name <> OLD.warehouse_type_name THEN
                    SET audit_log = CONCAT(audit_log, "Warehouse Type: ", OLD.warehouse_type_name, " -> ", NEW.warehouse_type_name, "<br/>");
                END IF;
                
                IF audit_log <> 'Warehouse type changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('warehouse_type', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_warehouse_type_insert
            AFTER INSERT ON warehouse_type
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Warehouse type created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('warehouse_type', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: WAREHOUSE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_warehouse_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_warehouse_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_warehouse_update
            AFTER UPDATE ON warehouse
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Warehouse changed.<br/><br/>';

                IF NEW.warehouse_name <> OLD.warehouse_name THEN
                    SET audit_log = CONCAT(audit_log, "Warehouse: ", OLD.warehouse_name, " -> ", NEW.warehouse_name, "<br/>");
                END IF;

                IF NEW.contact_person <> OLD.contact_person THEN
                    SET audit_log = CONCAT(audit_log, "Contact Person: ", OLD.contact_person, " -> ", NEW.contact_person, "<br/>");
                END IF;

                IF NEW.warehouse_status <> OLD.warehouse_status THEN
                    SET audit_log = CONCAT(audit_log, "Warehouse Status: ", OLD.warehouse_status, " -> ", NEW.warehouse_status, "<br/>");
                END IF;

                IF NEW.address <> OLD.address THEN
                    SET audit_log = CONCAT(audit_log, "Address: ", OLD.address, " -> ", NEW.address, "<br/>");
                END IF;

                IF NEW.city_name <> OLD.city_name THEN
                    SET audit_log = CONCAT(audit_log, "City: ", OLD.city_name, " -> ", NEW.city_name, "<br/>");
                END IF;

                IF NEW.state_name <> OLD.state_name THEN
                    SET audit_log = CONCAT(audit_log, "State: ", OLD.state_name, " -> ", NEW.state_name, "<br/>");
                END IF;

                IF NEW.country_name <> OLD.country_name THEN
                    SET audit_log = CONCAT(audit_log, "Country: ", OLD.country_name, " -> ", NEW.country_name, "<br/>");
                END IF;

                IF NEW.phone <> OLD.phone THEN
                    SET audit_log = CONCAT(audit_log, "Phone:", OLD.phone, " -> ", NEW.phone, "<br/>");
                END IF;

                IF NEW.telephone <> OLD.telephone THEN
                    SET audit_log = CONCAT(audit_log, "Telephone:", OLD.telephone, " -> ", NEW.telephone, "<br/>");
                END IF;

                IF NEW.email <> OLD.email THEN
                    SET audit_log = CONCAT(audit_log, "Email:", OLD.email, " -> ", NEW.email, "<br/>");
                END IF;
                
                IF audit_log <> 'Warehouse changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('warehouse', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_warehouse_insert
            AFTER INSERT ON warehouse
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Warehouse created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('warehouse', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: PRODUCT
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_product_update
            AFTER UPDATE ON product
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Product changed.<br/><br/>';

                IF NEW.product_name <> OLD.product_name THEN
                    SET audit_log = CONCAT(audit_log, "Product: ", OLD.product_name, " -> ", NEW.product_name, "<br/>");
                END IF;

                IF NEW.product_description <> OLD.product_description THEN
                    SET audit_log = CONCAT(audit_log, "Product Description: ", OLD.product_description, " -> ", NEW.product_description, "<br/>");
                END IF;

                IF NEW.sku <> OLD.sku THEN
                    SET audit_log = CONCAT(audit_log, "SKU: ", OLD.sku, " -> ", NEW.sku, "<br/>");
                END IF;

                IF NEW.barcode <> OLD.barcode THEN
                    SET audit_log = CONCAT(audit_log, "Barcode: ", OLD.barcode, " -> ", NEW.barcode, "<br/>");
                END IF;

                IF NEW.product_type <> OLD.product_type THEN
                    SET audit_log = CONCAT(audit_log, "Product Type: ", OLD.product_type, " -> ", NEW.product_type, "<br/>");
                END IF;

                IF NEW.base_price <> OLD.base_price THEN
                    SET audit_log = CONCAT(audit_log, "Base Price: ", OLD.base_price, " -> ", NEW.base_price, "<br/>");
                END IF;

                IF NEW.cost_price <> OLD.cost_price THEN
                    SET audit_log = CONCAT(audit_log, "Cost Price: ", OLD.cost_price, " -> ", NEW.cost_price, "<br/>");
                END IF;

                IF NEW.inventory_flow <> OLD.inventory_flow THEN
                    SET audit_log = CONCAT(audit_log, "Inventory Flow: ", OLD.inventory_flow, " -> ", NEW.inventory_flow, "<br/>");
                END IF;

                IF NEW.tax_classification <> OLD.tax_classification THEN
                    SET audit_log = CONCAT(audit_log, "Tax Classification: ", OLD.tax_classification, " -> ", NEW.tax_classification, "<br/>");
                END IF;

                IF NEW.track_inventory <> OLD.track_inventory THEN
                    SET audit_log = CONCAT(audit_log, "Track Inventory: ", OLD.track_inventory, " -> ", NEW.track_inventory, "<br/>");
                END IF;

                IF NEW.is_variant <> OLD.is_variant THEN
                    SET audit_log = CONCAT(audit_log, "Is Variant: ", OLD.is_variant, " -> ", NEW.is_variant, "<br/>");
                END IF;

                IF NEW.is_addon <> OLD.is_addon THEN
                    SET audit_log = CONCAT(audit_log, "Is Add On: ", OLD.is_addon, " -> ", NEW.is_addon, "<br/>");
                END IF;

                IF NEW.batch_tracking <> OLD.batch_tracking THEN
                    SET audit_log = CONCAT(audit_log, "Batch Tracking: ", OLD.batch_tracking, " -> ", NEW.batch_tracking, "<br/>");
                END IF;

                IF NEW.expiration_tracking <> OLD.expiration_tracking THEN
                    SET audit_log = CONCAT(audit_log, "Expiration Tracking: ", OLD.expiration_tracking, " -> ", NEW.expiration_tracking, "<br/>");
                END IF;

                IF NEW.parent_product_name <> OLD.parent_product_name THEN
                    SET audit_log = CONCAT(audit_log, "Parent Product: ", OLD.parent_product_name, " -> ", NEW.parent_product_name, "<br/>");
                END IF;

                IF NEW.variant_signature <> OLD.variant_signature THEN
                    SET audit_log = CONCAT(audit_log, "Variant Signature: ", OLD.variant_signature, " -> ", NEW.variant_signature, "<br/>");
                END IF;

                IF NEW.reorder_level <> OLD.reorder_level THEN
                    SET audit_log = CONCAT(audit_log, "Reorder Level: ", OLD.reorder_level, " -> ", NEW.reorder_level, "<br/>");
                END IF;

                IF NEW.base_unit_name <> OLD.base_unit_name THEN
                    SET audit_log = CONCAT(audit_log, "Base Unit: ", OLD.base_unit_name, " -> ", NEW.base_unit_name, "<br/>");
                END IF;

                IF NEW.base_unit_abbreviation <> OLD.base_unit_abbreviation THEN
                    SET audit_log = CONCAT(audit_log, "Base Unit Abbreviation: ", OLD.base_unit_abbreviation, " -> ", NEW.base_unit_abbreviation, "<br/>");
                END IF;
                
                IF audit_log <> 'Product changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('product', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_product_insert
            AFTER INSERT ON product
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Product created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('product', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: PRODUCT ATTRIBUTE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_attribute_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_attribute_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_product_attribute_update
            AFTER UPDATE ON product_attribute
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Product attribute changed.<br/><br/>';

                IF NEW.product_name <> OLD.product_name THEN
                    SET audit_log = CONCAT(audit_log, "Product: ", OLD.product_name, " -> ", NEW.product_name, "<br/>");
                END IF;

                IF NEW.attribute_name <> OLD.attribute_name THEN
                    SET audit_log = CONCAT(audit_log, "Attribute: ", OLD.attribute_name, " -> ", NEW.attribute_name, "<br/>");
                END IF;
                
                IF audit_log <> 'Product attribute changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('product_attribute', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_product_attribute_insert
            AFTER INSERT ON product_attribute
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Product attribute created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('product_attribute', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: PRODUCT CATEGORY MAP
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_category_map_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_category_map_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_product_category_map_update
            AFTER UPDATE ON product_category_map
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Product category map changed.<br/><br/>';

                IF NEW.product_name <> OLD.product_name THEN
                    SET audit_log = CONCAT(audit_log, "Product: ", OLD.product_name, " -> ", NEW.product_name, "<br/>");
                END IF;

                IF NEW.product_category_name <> OLD.product_category_name THEN
                    SET audit_log = CONCAT(audit_log, "Product Category: ", OLD.product_category_name, " -> ", NEW.product_category_name, "<br/>");
                END IF;
                
                IF audit_log <> 'Product category map changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('product_category_map', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_product_category_map_insert
            AFTER INSERT ON product_category_map
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Product category map created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('product_category_map', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: PRODUCT BON
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_bom_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_bom_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_product_bom_update
            AFTER UPDATE ON product_bom
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Product BOM changed.<br/><br/>';

                IF NEW.product_name <> OLD.product_name THEN
                    SET audit_log = CONCAT(audit_log, "Product: ", OLD.product_name, " -> ", NEW.product_name, "<br/>");
                END IF;

                IF NEW.bom_product_name <> OLD.bom_product_name THEN
                    SET audit_log = CONCAT(audit_log, "BOM Product: ", OLD.bom_product_name, " -> ", NEW.bom_product_name, "<br/>");
                END IF;

                IF NEW.quantity <> OLD.quantity THEN
                    SET audit_log = CONCAT(audit_log, "Quantity: ", OLD.quantity, " -> ", NEW.quantity, "<br/>");
                END IF;

                IF NEW.stock_policy <> OLD.stock_policy THEN
                    SET audit_log = CONCAT(audit_log, "Stock Policy: ", OLD.stock_policy, " -> ", NEW.stock_policy, "<br/>");
                END IF;
                
                IF audit_log <> 'Product BOM changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('product_bom', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_product_bom_insert
            AFTER INSERT ON product_bom
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Product BOM created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('product_bom', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: PRODUCT ADD-ON
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_addon_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_addon_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_product_addon_update
            AFTER UPDATE ON product_addon
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Product add-on changed.<br/><br/>';

                IF NEW.product_name <> OLD.product_name THEN
                    SET audit_log = CONCAT(audit_log, "Product: ", OLD.product_name, " -> ", NEW.product_name, "<br/>");
                END IF;

                IF NEW.addon_product_name <> OLD.addon_product_name THEN
                    SET audit_log = CONCAT(audit_log, "Add-On Product: ", OLD.addon_product_name, " -> ", NEW.addon_product_name, "<br/>");
                END IF;

                IF NEW.max_quantity <> OLD.max_quantity THEN
                    SET audit_log = CONCAT(audit_log, "Max Quantity: ", OLD.max_quantity, " -> ", NEW.max_quantity, "<br/>");
                END IF;
                
                IF audit_log <> 'Product add-on changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('product_addon', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_product_addon_insert
            AFTER INSERT ON product_addon
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Product add-on created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('product_addon', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: INVENTORY LOT
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_inventory_lot_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_inventory_lot_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_inventory_lot_update
            AFTER UPDATE ON inventory_lot
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Inventory lot changed.<br/><br/>';

                IF NEW.product_name <> OLD.product_name THEN
                    SET audit_log = CONCAT(audit_log, "Product: ", OLD.product_name, " -> ", NEW.product_name, "<br/>");
                END IF;

                IF NEW.batch_number <> OLD.batch_number THEN
                    SET audit_log = CONCAT(audit_log, "Batch Number: ", OLD.batch_number, " -> ", NEW.batch_number, "<br/>");
                END IF;

                IF NEW.cost_per_unit <> OLD.cost_per_unit THEN
                    SET audit_log = CONCAT(audit_log, "Cost Per Unit: ", OLD.cost_per_unit, " -> ", NEW.cost_per_unit, "<br/>");
                END IF;

                IF NEW.expiration_date <> OLD.expiration_date THEN
                    SET audit_log = CONCAT(audit_log, "Expiration Date: ", OLD.expiration_date, " -> ", NEW.expiration_date, "<br/>");
                END IF;

                IF NEW.received_date <> OLD.received_date THEN
                    SET audit_log = CONCAT(audit_log, "Received Date: ", OLD.received_date, " -> ", NEW.received_date, "<br/>");
                END IF;
                
                IF audit_log <> 'Inventory lot changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('inventory_lot', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_inventory_lot_insert
            AFTER INSERT ON inventory_lot
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Inventory lot created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('inventory_lot', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: STOCK LEVEL
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_level_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_level_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_level_update
            AFTER UPDATE ON stock_level
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock level changed.<br/><br/>';

                IF NEW.product_name <> OLD.product_name THEN
                    SET audit_log = CONCAT(audit_log, "Product: ", OLD.product_name, " -> ", NEW.product_name, "<br/>");
                END IF;

                IF NEW.warehouse_name <> OLD.warehouse_name THEN
                    SET audit_log = CONCAT(audit_log, "Warehouse: ", OLD.warehouse_name, " -> ", NEW.warehouse_name, "<br/>");
                END IF;

                IF NEW.inventory_lot_id <> OLD.inventory_lot_id THEN
                    SET audit_log = CONCAT(audit_log, "Inventory Lot ID: ", OLD.inventory_lot_id, " -> ", NEW.inventory_lot_id, "<br/>");
                END IF;

                IF NEW.stock_status <> OLD.stock_status THEN
                    SET audit_log = CONCAT(audit_log, "Stock Status: ", OLD.stock_status, " -> ", NEW.stock_status, "<br/>");
                END IF;

                IF NEW.quantity <> OLD.quantity THEN
                    SET audit_log = CONCAT(audit_log, "Quantity: ", OLD.quantity, " -> ", NEW.quantity, "<br/>");
                END IF;
                
                IF audit_log <> 'Stock level changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('stock_level', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_level_insert
            AFTER INSERT ON stock_level
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock level created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('stock_level', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: STOCK BATCH
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_batch_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_batch_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_batch_update
            AFTER UPDATE ON stock_batch
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock batch changed.<br/><br/>';

                IF NEW.reference_number <> OLD.reference_number THEN
                    SET audit_log = CONCAT(audit_log, "Reference Number: ", OLD.reference_number, " -> ", NEW.reference_number, "<br/>");
                END IF;

                IF NEW.warehouse_name <> OLD.warehouse_name THEN
                    SET audit_log = CONCAT(audit_log, "Warehouse: ", OLD.warehouse_name, " -> ", NEW.warehouse_name, "<br/>");
                END IF;

                IF NEW.stock_batch_status <> OLD.stock_batch_status THEN
                    SET audit_log = CONCAT(audit_log, "Stock Batch Status: ", OLD.stock_batch_status, " -> ", NEW.stock_batch_status, "<br/>");
                END IF;
                
                IF NEW.remarks <> OLD.remarks THEN
                    SET audit_log = CONCAT(audit_log, "Remarks: ", OLD.remarks, " -> ", NEW.remarks, "<br/>");
                END IF;
                
                IF NEW.for_approval_date <> OLD.for_approval_date THEN
                    SET audit_log = CONCAT(audit_log, "For Approval Date: ", OLD.for_approval_date, " -> ", NEW.for_approval_date, "<br/>");
                END IF;
                
                IF NEW.approved_date <> OLD.approved_date THEN
                    SET audit_log = CONCAT(audit_log, "Approved Date: ", OLD.approved_date, " -> ", NEW.approved_date, "<br/>");
                END IF;

                IF NEW.cancellation_date <> OLD.cancellation_date THEN
                    SET audit_log = CONCAT(audit_log, "Cancellation Date: ", OLD.cancellation_date, " -> ", NEW.cancellation_date, "<br/>");
                END IF;

                IF NEW.set_to_draft_date <> OLD.set_to_draft_date THEN
                    SET audit_log = CONCAT(audit_log, "Set to Draft Date: ", OLD.set_to_draft_date, " -> ", NEW.set_to_draft_date, "<br/>");
                END IF;
                
                IF audit_log <> 'Stock batch changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('stock_batch', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_batch_insert
            AFTER INSERT ON stock_batch
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock batch created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('stock_batch', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: STOCK BATCH ITEM
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_batch_items_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_batch_items_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_batch_items_update
            AFTER UPDATE ON stock_batch_items
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock batch item changed.<br/><br/>';

                IF NEW.product_name <> OLD.product_name THEN
                    SET audit_log = CONCAT(audit_log, "Product: ", OLD.product_name, " -> ", NEW.product_name, "<br/>");
                END IF;

                IF NEW.batch_number <> OLD.batch_number THEN
                    SET audit_log = CONCAT(audit_log, "Batch Number: ", OLD.batch_number, " -> ", NEW.batch_number, "<br/>");
                END IF;

                IF NEW.cost_per_unit <> OLD.cost_per_unit THEN
                    SET audit_log = CONCAT(audit_log, "Cost per Unit: ", OLD.cost_per_unit, " -> ", NEW.cost_per_unit, "<br/>");
                END IF;
                
                IF NEW.expiration_date <> OLD.expiration_date THEN
                    SET audit_log = CONCAT(audit_log, "Expiration Date: ", OLD.expiration_date, " -> ", NEW.expiration_date, "<br/>");
                END IF;
                
                IF NEW.received_date <> OLD.received_date THEN
                    SET audit_log = CONCAT(audit_log, "Received Date: ", OLD.received_date, " -> ", NEW.received_date, "<br/>");
                END IF;
                
                IF NEW.quantity <> OLD.quantity THEN
                    SET audit_log = CONCAT(audit_log, "Quantity: ", OLD.quantity, " -> ", NEW.quantity, "<br/>");
                END IF;

                IF audit_log <> 'Stock batch item changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('stock_batch_items', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_batch_items_insert
            AFTER INSERT ON stock_batch_items
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock batch item created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('stock_batch_items', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: STOCK ADJUSTMENT
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_adjustment_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_adjustment_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_adjustment_update
            AFTER UPDATE ON stock_adjustment
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock adjustment changed.<br/><br/>';

                IF NEW.reference_number <> OLD.reference_number THEN
                    SET audit_log = CONCAT(audit_log, "Reference Number: ", OLD.reference_number, " -> ", NEW.reference_number, "<br/>");
                END IF;

                IF NEW.stock_adjustment_status <> OLD.stock_adjustment_status THEN
                    SET audit_log = CONCAT(audit_log, "Stock Adjustment Status: ", OLD.stock_adjustment_status, " -> ", NEW.stock_adjustment_status, "<br/>");
                END IF;
                
                IF NEW.stock_adjustment_reason_name <> OLD.stock_adjustment_reason_name THEN
                    SET audit_log = CONCAT(audit_log, "Stock Adjustment Reason: ", OLD.stock_adjustment_reason_name, " -> ", NEW.stock_adjustment_reason_name, "<br/>");
                END IF;

                IF NEW.remarks <> OLD.remarks THEN
                    SET audit_log = CONCAT(audit_log, "Remarks: ", OLD.remarks, " -> ", NEW.remarks, "<br/>");
                END IF;

                IF NEW.for_approval_date <> OLD.for_approval_date THEN
                    SET audit_log = CONCAT(audit_log, "For Approval Date: ", OLD.for_approval_date, " -> ", NEW.for_approval_date, "<br/>");
                END IF;

                IF NEW.approved_date <> OLD.approved_date THEN
                    SET audit_log = CONCAT(audit_log, "Approved Date: ", OLD.approved_date, " -> ", NEW.approved_date, "<br/>");
                END IF;

                IF NEW.cancellation_date <> OLD.cancellation_date THEN
                    SET audit_log = CONCAT(audit_log, "Cancellation Date: ", OLD.cancellation_date, " -> ", NEW.cancellation_date, "<br/>");
                END IF;

                IF NEW.set_to_draft_date <> OLD.set_to_draft_date THEN
                    SET audit_log = CONCAT(audit_log, "Set to Draft Date: ", OLD.set_to_draft_date, " -> ", NEW.set_to_draft_date, "<br/>");
                END IF;
                
                IF audit_log <> 'Stock adjustment changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('stock_adjustment', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_adjustment_insert
            AFTER INSERT ON stock_adjustment
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock adjustment created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('stock_adjustment', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: STOCK ADJUSTMENT
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_adjustment_items_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_adjustment_items_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_adjustment_items_update
            AFTER UPDATE ON stock_adjustment_items
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock adjustment items changed.<br/><br/>';

                IF NEW.stock_level_id <> OLD.stock_level_id THEN
                    SET audit_log = CONCAT(audit_log, "Stock Level ID: ", OLD.stock_level_id, " -> ", NEW.stock_level_id, "<br/>");
                END IF;

                IF NEW.adjustment_type <> OLD.adjustment_type THEN
                    SET audit_log = CONCAT(audit_log, "Adjustment Type: ", OLD.adjustment_type, " -> ", NEW.adjustment_type, "<br/>");
                END IF;

                IF NEW.adjustment_quantity <> OLD.adjustment_quantity THEN
                    SET audit_log = CONCAT(audit_log, "Adjustment Quantity: ", OLD.adjustment_quantity, " -> ", NEW.adjustment_quantity, "<br/>");
                END IF;

                IF NEW.current_quantity <> OLD.current_quantity THEN
                    SET audit_log = CONCAT(audit_log, "Current Quantity: ", OLD.current_quantity, " -> ", NEW.current_quantity, "<br/>");
                END IF;

                IF NEW.new_quantity <> OLD.new_quantity THEN
                    SET audit_log = CONCAT(audit_log, "New Quantity: ", OLD.new_quantity, " -> ", NEW.new_quantity, "<br/>");
                END IF;
                
                IF audit_log <> 'Stock adjustment items changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('stock_adjustment_items', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_adjustment_items_insert
            AFTER INSERT ON stock_adjustment_items
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock adjustment items created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('stock_adjustment_items', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: STOCK TRANSFER
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_transfer_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_transfer_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_transfer_update
            AFTER UPDATE ON stock_transfer
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock transfer changed.<br/><br/>';

                IF NEW.reference_number <> OLD.reference_number THEN
                    SET audit_log = CONCAT(audit_log, "Reference Number: ", OLD.reference_number, " -> ", NEW.reference_number, "<br/>");
                END IF;

                IF NEW.from_warehouse_name <> OLD.from_warehouse_name THEN
                    SET audit_log = CONCAT(audit_log, "From Warehouse: ", OLD.from_warehouse_name, " -> ", NEW.from_warehouse_name, "<br/>");
                END IF;

                IF NEW.to_warehouse_name <> OLD.to_warehouse_name THEN
                    SET audit_log = CONCAT(audit_log, "To Warehouse: ", OLD.to_warehouse_name, " -> ", NEW.to_warehouse_name, "<br/>");
                END IF;

                IF NEW.stock_transfer_status <> OLD.stock_transfer_status THEN
                    SET audit_log = CONCAT(audit_log, "Stock Transfer Status: ", OLD.stock_transfer_status, " -> ", NEW.stock_transfer_status, "<br/>");
                END IF;

                IF NEW.stock_transfer_reason_name <> OLD.stock_transfer_reason_name THEN
                    SET audit_log = CONCAT(audit_log, "Stock Transfer Reason: ", OLD.stock_transfer_reason_name, " -> ", NEW.stock_transfer_reason_name, "<br/>");
                END IF;

                IF NEW.remarks <> OLD.remarks THEN
                    SET audit_log = CONCAT(audit_log, "Remarks: ", OLD.remarks, " -> ", NEW.remarks, "<br/>");
                END IF;

                IF NEW.for_approval_date <> OLD.for_approval_date THEN
                    SET audit_log = CONCAT(audit_log, "For Approval Date: ", OLD.for_approval_date, " -> ", NEW.for_approval_date, "<br/>");
                END IF;

                IF NEW.approved_date <> OLD.approved_date THEN
                    SET audit_log = CONCAT(audit_log, "Approved Date: ", OLD.approved_date, " -> ", NEW.approved_date, "<br/>");
                END IF;

                IF NEW.cancellation_date <> OLD.cancellation_date THEN
                    SET audit_log = CONCAT(audit_log, "Cancellation Date: ", OLD.cancellation_date, " -> ", NEW.cancellation_date, "<br/>");
                END IF;

                IF NEW.set_to_draft_date <> OLD.set_to_draft_date THEN
                    SET audit_log = CONCAT(audit_log, "Set to Draft Date: ", OLD.set_to_draft_date, " -> ", NEW.set_to_draft_date, "<br/>");
                END IF;
                
                IF audit_log <> 'Stock transfer changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('stock_transfer', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_transfer_insert
            AFTER INSERT ON stock_transfer
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock transfer created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('stock_transfer', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
            END
        SQL);

        /* =============================================================================================
            TABLE: STOCK TRANSFER ITEMS
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_transfer_items_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_transfer_items_insert');

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_transfer_items_update
            AFTER UPDATE ON stock_transfer_items
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock transfer items changed.<br/><br/>';

                IF NEW.stock_level_id <> OLD.stock_level_id THEN
                    SET audit_log = CONCAT(audit_log, "Stock Level ID: ", OLD.stock_level_id, " -> ", NEW.stock_level_id, "<br/>");
                END IF;

                IF NEW.quantity <> OLD.quantity THEN
                    SET audit_log = CONCAT(audit_log, "Quantity: ", OLD.quantity, " -> ", NEW.quantity, "<br/>");
                END IF;
                
                IF audit_log <> 'Stock transfer items changed.<br/><br/>' THEN
                    INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                    VALUES ('stock_transfer_items', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
                END IF;
            END
        SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER trg_stock_transfer_items_insert
            AFTER INSERT ON stock_transfer_items
            FOR EACH ROW
            BEGIN
                DECLARE audit_log TEXT DEFAULT 'Stock transfer items created.';

                INSERT INTO audit_log (table_name, reference_id, log, changed_by, created_at) 
                VALUES ('stock_transfer_items', NEW.id, audit_log, NEW.last_log_by, new.updated_at);
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

        /* =============================================================================================
            TABLE: COUNTRY
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_country_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_country_insert');

        /* =============================================================================================
            TABLE: STATE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_state_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_state_insert');

        /* =============================================================================================
            TABLE: CITY
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_city_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_city_insert');

        /* =============================================================================================
            TABLE: NATIONALITY
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_nationality_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_nationality_insert');

        /* =============================================================================================
            TABLE: CURRENCY
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_currency_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_currency_insert');

        /* =============================================================================================
            TABLE: COMPANY
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_company_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_company_insert');

        /* =============================================================================================
            TABLE: ATTRIBUTE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_attribute_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_attribute_insert');

        /* =============================================================================================
            TABLE: ATTRIBUTE VALUE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_attribute_value_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_attribute_value_insert');

        /* =============================================================================================
            TABLE: PRODUCT CATEGORY
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_category_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_category_insert');

        /* =============================================================================================
            TABLE: STOCK ADJUSTMENT REASON
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_adjustment_reason_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_adjustment_reason_insert');

        /* =============================================================================================
            TABLE: STOCK TRANSFER REASON 
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_transfer_reason_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_transfer_reason_insert');

        /* =============================================================================================
            TABLE: SUPPLIER
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_supplier_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_supplier_insert');

        /* =============================================================================================
            TABLE: UNIT TYPE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_unit_type_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_unit_type_insert');

        /* =============================================================================================
            TABLE: UNIT
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_unit_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_unit_insert');

        /* =============================================================================================
            TABLE: WAREHOUSE TYPE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_warehouse_type_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_warehouse_type_insert');

        /* =============================================================================================
            TABLE: WAREHOUSE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_warehouse_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_warehouse_insert');

        /* =============================================================================================
            TABLE: PRODUCT
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_insert');

        /* =============================================================================================
            TABLE: STOCK TRANSFER
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_transfer_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_transfer_insert');

        /* =============================================================================================
            TABLE: PRODUCT ATTRIBUTE
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_attribute_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_attribute_insert');

        /* =============================================================================================
            TABLE: PRODUCT BOM
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_bom_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_bom_insert');

        /* =============================================================================================
            TABLE: PRODUCT ADD-ON
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_addon_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_addon_insert');

        /* =============================================================================================
            TABLE: PRODUCT CATEGORY MAP
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_category_map_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_product_category_map_insert');

         /* =============================================================================================
            TABLE: INVENTORY LOT
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_inventory_lot_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_inventory_lot_insert');

        /* =============================================================================================
            TABLE: STOCK LEVEL
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_level_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_level_insert');

        /* =============================================================================================
            TABLE: STOCK BATCH
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_batch_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_batch_insert');
        
        /* =============================================================================================
            TABLE: STOCK BATCH ITEM
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_batch_items_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_batch_items_insert');

        /* =============================================================================================
            TABLE: STOCK ADJUSTMENT
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_adjustment_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_adjustment_insert');

        /* =============================================================================================
            TABLE: STOCK ADJUSTMENT ITEMS
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_adjustment_items_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_adjustment_items_insert');

        /* =============================================================================================
            TABLE: STOCK TRANSFER
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_transfer_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_transfer_insert');

        /* =============================================================================================
            TABLE: STOCK TRANSFER ITEMS
        ============================================================================================= */

        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_transfer_items_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_stock_transfer_items_insert');
    }
};
