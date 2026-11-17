<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute une icône et un indicateur « active » aux catégories.
 */
final class Version20251001090601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Icône et activation des catégories';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE categorie
                ADD icone VARCHAR(255) DEFAULT 'fas fa-folder' NOT NULL,
                ADD active TINYINT DEFAULT 1 NOT NULL
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE categorie DROP icone, DROP active');
    }
}
