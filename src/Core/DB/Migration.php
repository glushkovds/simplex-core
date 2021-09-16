<?php
namespace Simplex\Core\DB;

interface Migration
{
    public function up(Schema $schema): bool;
    public function down(Schema $schema): bool;
}