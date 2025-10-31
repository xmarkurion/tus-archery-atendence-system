<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Every selected day will be an automatically created meeting
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->integer('sessions_attended')->default(0);
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->integer('pin')->nullable();
            $table->text('info')->nullable();
            $table->timestamps();
        });

        // Create a trigger that assigns a random 4-digit pin (1000-9999) for new meetings when pin is NULL.
        // Use driver-specific SQL for SQLite, MySQL/MariaDB, and PostgreSQL.
        $driver = DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            // SQLite trigger: AFTER INSERT ... WHEN NEW.pin IS NULL
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS set_meeting_pin AFTER INSERT ON meetings
                WHEN NEW.pin IS NULL
                BEGIN
                    UPDATE meetings
                    SET pin = (abs(random()) % 9000) + 1000
                    WHERE id = NEW.id;
                END;
                SQL
            );
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            // MySQL/MariaDB: BEFORE INSERT trigger to set NEW.pin
            DB::unprepared('DROP TRIGGER IF EXISTS set_meeting_pin');
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER set_meeting_pin BEFORE INSERT ON meetings
                FOR EACH ROW
                BEGIN
                    IF NEW.pin IS NULL THEN
                        SET NEW.pin = FLOOR(RAND()*9000) + 1000;
                    END IF;
                END;
                SQL
            );
        } elseif ($driver === 'pgsql' || $driver === 'postgresql') {
            // PostgreSQL: create function + trigger
            DB::unprepared('DROP TRIGGER IF EXISTS set_meeting_pin ON meetings');
            DB::unprepared('DROP FUNCTION IF EXISTS set_meeting_pin_func()');
            DB::unprepared(<<<'SQL'
                CREATE FUNCTION set_meeting_pin_func() RETURNS trigger AS $$
                BEGIN
                    IF NEW.pin IS NULL THEN
                        NEW.pin := floor(random() * 9000)::int + 1000;
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;

                CREATE TRIGGER set_meeting_pin
                BEFORE INSERT ON meetings
                FOR EACH ROW
                EXECUTE FUNCTION set_meeting_pin_func();
                SQL
            );
        } else {
            // fallback: no trigger created
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS set_meeting_pin');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::unprepared('DROP TRIGGER IF EXISTS set_meeting_pin');
        } elseif ($driver === 'pgsql' || $driver === 'postgresql') {
            DB::unprepared('DROP TRIGGER IF EXISTS set_meeting_pin ON meetings');
            DB::unprepared('DROP FUNCTION IF EXISTS set_meeting_pin_func()');
        }

        Schema::dropIfExists('meetings');
    }
};
