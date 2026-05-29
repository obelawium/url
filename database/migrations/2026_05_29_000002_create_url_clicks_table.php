<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Obelaw\Ium\Url\Base\MigrationBase;

return new class extends MigrationBase
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->prefix . 'clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')
                ->constrained($this->prefix . 'links')
                ->onDelete('cascade');
            $table->string('ip_address', 45)->nullable(); // Accommodates both IPv4 and IPv6
            $table->text('user_agent')->nullable();
            $table->string('device_type', 20)->default('other');
            $table->text('referrer')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix . 'clicks');
    }
};
