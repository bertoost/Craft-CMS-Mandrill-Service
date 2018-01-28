<?php

namespace Craft;

/**
 * Class m180128_161000_mandrill_AddOutboundTable
 */
class m180128_161000_mandrill_AddOutboundTable extends BaseMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Craft::log('Creating the mandrill-outbound table.');

        craft()->db->createCommand()->createTable('mandrill_outbound', [
            'messageId'    => ['column' => ColumnType::Varchar, 'null' => false],
            'messageTs'    => ['column' => ColumnType::DateTime, 'null' => false],
            'sender'       => ['column' => ColumnType::Varchar],
            'subject'      => ['column' => ColumnType::Varchar],
            'to'           => ['column' => ColumnType::Varchar, 'null' => false],
            'opens'        => ['column' => ColumnType::Int, 'default' => 0],
            'clicks'       => ['column' => ColumnType::Int, 'default' => 0],
            'state'        => ['column' => ColumnType::Varchar, 'default' => Mandrill_OutboundModel::STATE_SENT],
            'rejectReason' => ['column' => ColumnType::Varchar],
        ]);

        Craft::log('Finished creating the mandrill-outbound table.');

        return true;
    }
}
