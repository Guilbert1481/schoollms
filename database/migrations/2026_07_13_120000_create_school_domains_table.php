<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Host -> school mapping for host-based multi-tenancy.
     *
     * One school may own many hosts: its base-domain subdomain, one or more
     * custom domains (each school can bring its own), and aliases. The Host
     * header of every request is matched here (see App\Support\Tenancy\
     * TenantResolver). Subdomains of our base domain also resolve by the
     * school slug convention and do not strictly need a row here; custom
     * domains DO, and `is_verified` gates on-demand TLS issuance for them.
     */
    public function up(): void
    {
        Schema::create('school_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            // Full hostname, always stored lowercased (see SchoolDomain model).
            $table->string('host')->unique();

            // 'subdomain' (of our base domain) | 'custom' (school's own domain).
            $table->string('type')->default('custom');

            // The canonical host for the school — used when building absolute
            // URLs / redirects back to a user's own school.
            $table->boolean('is_primary')->default(false);

            // Custom domains are only served / issued a TLS cert once verified
            // (DNS pointed at us + confirmed). Base-domain subdomains we control.
            $table->boolean('is_verified')->default(false);

            $table->timestamps();

            $table->index(['school_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_domains');
    }
};
