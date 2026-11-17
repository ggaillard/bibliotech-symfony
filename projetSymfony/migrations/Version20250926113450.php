<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute la clé étrangère livre.categorie_id → categorie.id
 *
 * Une migration qui MODIFIE une table existante (ALTER TABLE) : c'est ainsi qu'on fait
 * évoluer une base déjà en service, sans la recréer.
 */
final class Version20250926113450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clé étrangère livre.categorie_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE livre ADD categorie_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_AC634F99BCF5E72D ON livre (categorie_id)');
        // ON DELETE SET NULL : catégorie supprimée → livre « non classé »
        $this->addSql('ALTER TABLE livre ADD CONSTRAINT FK_AC634F99BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE livre DROP FOREIGN KEY FK_AC634F99BCF5E72D');
        $this->addSql('DROP INDEX IDX_AC634F99BCF5E72D ON livre');
        $this->addSql('ALTER TABLE livre DROP categorie_id');
    }
}
