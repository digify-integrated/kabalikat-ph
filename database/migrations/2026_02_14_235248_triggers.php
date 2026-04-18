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

                IF NEW.is_base_unit <> OLD.is_base_unit THEN
                    SET audit_log = CONCAT(audit_log, "Is Base Unit: ", OLD.is_base_unit, " -> ", NEW.is_base_unit, "<br/>");
                END IF;

                IF NEW.conversion_factor <> OLD.conversion_factor THEN
                    SET audit_log = CONCAT(audit_log, "Conversion Factor: ", OLD.conversion_factor, " -> ", NEW.conversion_factor, "<br/>");
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
                    SET audit_log = CONCAT(audit_log, "Supplier: ", OLD.warehouse_name, " -> ", NEW.warehouse_name, "<br/>");
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
    }
};
