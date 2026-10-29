<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Models\User;
use Override;
use Yii;
use yii\base\Model;

class McpTokenForm extends Model
{
    public int|string|null $expires = null;
    public bool|string $readOnly = false;

    /**
     * `$lifetimes` are the days a token may last, besides never expiring.
     *
     * @param list<int> $lifetimes
     * @param array<string, mixed> $config
     */
    public function __construct(
        public readonly User $user,
        public readonly array $lifetimes = [30, 90, 365],
        ?int $defaultLifetime = 90,
        array $config = [],
    ) {
        $this->expires = $defaultLifetime;
        parent::__construct($config);
    }

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['expires'],
                'in',
                'range' => $this->lifetimes,
            ],
            [
                ['readOnly'],
                'boolean',
            ],
        ];
    }

    /**
     * The token is shown once; `null` when the form did not validate.
     */
    public function create(): ?string
    {
        if (!$this->validate()) {
            return null;
        }

        $days = (int)$this->expires;

        return Mcp::getComponent()->issueToken($this->user, (bool)$this->readOnly, $days ? $days * 86400 : null);
    }

    /**
     * @return array<int, string>
     */
    public function getExpiresItems(): array
    {
        $items = [];

        foreach ($this->lifetimes as $days) {
            $items[$days] = Yii::t('skeleton', 'MCP_TOKEN_EXPIRES_DAYS_NAME', ['days' => $days]);
        }

        return $items;
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'expires' => Yii::t('skeleton', 'MCP_TOKEN_EXPIRES_AT_LABEL'),
            'readOnly' => Yii::t('skeleton', 'GRANT_READ_ONLY_LABEL'),
        ];
    }
}
