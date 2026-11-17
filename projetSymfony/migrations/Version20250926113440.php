<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crée la table des livres.
 */
final class Version20250926113440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Table livre';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE livre (
                id INT AUTO_INCREMENT NOT NULL,
                titre VARCHAR(255) NOT NULL,
                auteur VARCHAR(255) NOT NULL,
                annee SMALLINT UNSIGNED DEFAULT NULL,
                nb_pages INT UNSIGNED DEFAULT NULL,
                isbn VARCHAR(255) DEFAULT NULL,
                resume LONGTEXT DEFAULT NULL,
                disponible TINYINT DEFAULT 1 NOT NULL,
                UNIQUE INDEX UNIQ_AC634F99CC1CF4E6 (isbn),
                INDEX idx_livre_titre_auteur (titre, auteur),
                INDEX idx_livre_disponible (disponible),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
            SQL);
        // auteur : du texte libre pour l'instant (voir séance 2)
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE livre');
    }
}
