<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add schema modification to support ReceiverSourceMerger
 */
class Version20171207201319 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Add schema modification to support ReceiverSourceMerger';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE sandstorm_newsletter_domain_model_receiversource ADD sources LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:flow_json_array)\'');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE sandstorm_newsletter_domain_model_receiversource DROP sources');
    }
}
