<?php
namespace Simplex\Core\DB;

interface Migration
{
    public function up(): bool;
    public function down(): bool;
}