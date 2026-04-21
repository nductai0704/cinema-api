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
        if (! Schema::hasTable('regions')) {
            Schema::create('regions', function (Blueprint $table) {
                $table->id('region_id');
                $table->string('city', 100);
                $table->string('district', 100);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id('role_id');
                $table->string('role_name', 50)->unique();
                $table->string('description', 255)->nullable();
                $table->string('status', 50)->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cinemas')) {
            Schema::create('cinemas', function (Blueprint $table) {
                $table->id('cinema_id');
                $table->string('cinema_name', 255);
                $table->foreignId('region_id')->nullable()->constrained('regions', 'region_id')->nullOnDelete();
                $table->string('address', 255)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('district', 100)->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('status', 50)->default('active');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role_id') && Schema::hasColumn('users', 'cinema_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('role_id')->references('role_id')->on('roles')->cascadeOnDelete();
                $table->foreign('cinema_id')->references('cinema_id')->on('cinemas')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id('room_id');
                $table->string('room_name', 100)->nullable();
                $table->foreignId('cinema_id')->constrained('cinemas', 'cinema_id')->cascadeOnDelete();
                $table->integer('capacity')->nullable();
                $table->string('room_type', 50)->nullable();
                $table->string('status', 50)->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('seats')) {
            Schema::create('seats', function (Blueprint $table) {
                $table->id('seat_id');
                $table->foreignId('room_id')->constrained('rooms', 'room_id')->cascadeOnDelete();
                $table->char('row_label', 1)->nullable();
                $table->integer('seat_number')->nullable();
                $table->string('seat_type', 50)->nullable();
                $table->string('status', 50)->default('active');
                $table->timestamps();
                $table->unique(['room_id', 'row_label', 'seat_number']);
            });
        }

        if (! Schema::hasTable('movies')) {
            Schema::create('movies', function (Blueprint $table) {
                $table->id('movie_id');
                $table->string('title', 255);
                $table->integer('duration')->nullable();
                $table->longText('description')->nullable();
                $table->string('language', 100)->nullable();
                $table->date('release_date')->nullable();
                $table->date('end_date')->nullable();
                $table->integer('age_limit')->nullable();
                $table->string('poster_url', 255)->nullable();
                $table->string('trailer_url', 255)->nullable();
                $table->decimal('rating', 3, 1)->nullable();
                $table->string('backdrop_url', 255)->nullable();
                $table->longText('actors')->nullable();
                $table->string('director', 255)->nullable();
                $table->string('country', 100)->nullable();
                $table->string('producer', 255)->nullable();
                $table->string('status', 50)->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('genres')) {
            Schema::create('genres', function (Blueprint $table) {
                $table->id('genre_id');
                $table->string('genre_name', 100);
                $table->string('status', 50)->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('movie_genres')) {
            Schema::create('movie_genres', function (Blueprint $table) {
                $table->unsignedBigInteger('movie_id');
                $table->unsignedBigInteger('genre_id');
                $table->primary(['movie_id', 'genre_id']);
                $table->foreign('movie_id')->references('movie_id')->on('movies')->cascadeOnDelete();
                $table->foreign('genre_id')->references('genre_id')->on('genres')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('showtimes')) {
            Schema::create('showtimes', function (Blueprint $table) {
                $table->id('showtime_id');
                $table->foreignId('movie_id')->constrained('movies', 'movie_id')->cascadeOnDelete();
                $table->foreignId('room_id')->constrained('rooms', 'room_id')->cascadeOnDelete();
                $table->date('show_date')->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->decimal('ticket_price', 10, 2)->default(0);
                $table->string('status', 50)->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id('booking_id');
                $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $table->timestamp('booking_time')->useCurrent();
                $table->decimal('total_amount', 10, 2)->nullable();
                $table->string('status', 50)->default('pending');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('combos')) {
            Schema::create('combos', function (Blueprint $table) {
                $table->id('combo_id');
                $table->string('combo_name', 255);
                $table->decimal('price', 10, 2)->default(0);
                $table->longText('description')->nullable();
                $table->string('image_url', 255)->nullable();
                $table->string('status', 50)->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('booking_combos')) {
            Schema::create('booking_combos', function (Blueprint $table) {
                $table->id('booking_combo_id');
                $table->foreignId('booking_id')->constrained('bookings', 'booking_id')->cascadeOnDelete();
                $table->foreignId('combo_id')->constrained('combos', 'combo_id')->cascadeOnDelete();
                $table->integer('quantity')->default(1);
                $table->decimal('price', 10, 2)->default(0);
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id('payment_id');
                $table->foreignId('booking_id')->constrained('bookings', 'booking_id')->cascadeOnDelete();
                $table->string('payment_method', 50)->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('payment_status', 50)->nullable();
                $table->timestamp('payment_time')->useCurrent();
            });
        }

        if (! Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id('ticket_id');
                $table->foreignId('booking_id')->constrained('bookings', 'booking_id')->cascadeOnDelete();
                $table->foreignId('showtime_id')->constrained('showtimes', 'showtime_id')->cascadeOnDelete();
                $table->foreignId('seat_id')->constrained('seats', 'seat_id')->cascadeOnDelete();
                $table->string('ticket_code', 50)->unique();
                $table->string('qr_code', 180)->unique();
                $table->decimal('ticket_price', 10, 2)->default(0);
                $table->string('status', 50)->default('booked');
                $table->timestamps();
                $table->unique(['showtime_id', 'seat_id']);
            });
        }

        if (! Schema::hasTable('seat_holds')) {
            Schema::create('seat_holds', function (Blueprint $table) {
                $table->id('hold_id');
                $table->foreignId('showtime_id')->constrained('showtimes', 'showtime_id')->cascadeOnDelete();
                $table->foreignId('seat_id')->constrained('seats', 'seat_id')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $table->timestamp('hold_time')->useCurrent();
                $table->timestamp('expired_time')->nullable();
                $table->string('status', 50)->default('active');
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->id('news_id');
                $table->string('title', 255);
                $table->longText('content')->nullable();
                $table->string('image_url', 255)->nullable();
                $table->foreignId('created_by')->constrained('users', 'user_id')->cascadeOnDelete();
                $table->string('status', 50)->default('active');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
        Schema::dropIfExists('seat_holds');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('booking_combos');
        Schema::dropIfExists('combos');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('showtimes');
        Schema::dropIfExists('movie_genres');
        Schema::dropIfExists('genres');
        Schema::dropIfExists('movies');
        Schema::dropIfExists('seats');
        Schema::dropIfExists('rooms');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['cinema_id']);
        });
        Schema::dropIfExists('cinemas');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('regions');
    }
};
