<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crée la table des catégories (Roman, Policier, Informatique…).
 *
 * Une migration Doctrine est du SQL rangé dans une classe PHP, avec sa date dans le nom :
 * toute l'équipe (et le serveur de production) rejoue les mêmes migrations, dans le même ordre.
 */
final class Version20250926113430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Table categorie';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE categorie (
                id INT AUTO_INCREMENT NOT NULL,
                nom VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                slug VARCHAR(255) NOT NULL,
                couleur VARCHAR(7) DEFAULT '#6B7280' NOT NULL,
                UNIQUE INDEX UNIQ_497DD634989D9B62 (slug),
                INDEX idx_categorie_nom (nom),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE categorie');
    }
}
