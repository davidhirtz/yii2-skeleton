<?php
/**
 * This is the template for generating the model class of a specified table.
 *
 * @var yii\web\View $this
 * @var yii\gii\generators\model\Generator $generator
 * @var string  $tableName full table name
 * @var string $className class name
 * @var string $queryClassName query class name
 * @var yii\db\TableSchema $tableSchema
 * @var array  $properties list of properties (property => [type, name, comment])
 * @var string[]  $labels list of attribute labels (name => label)
 * @var string[] $rules list of validation rules
 * @var array $relations list of relations (name => relation declaration)
 * @var array $relationsClassHints list of class hints for relations (name => relation declaration)
 */

echo "<?php\n";
?>

namespace <?= $generator->ns ?>;

use Yii;

/**
 * <?= $className ."\n" ?>
 *
<?php foreach ($properties as $property => $data): ?>
 * @property <?= "{$data['type']} \${$property}"  . ($data['comment'] ? ' ' . strtr($data['comment'], ["\n" => ' ']) : '') . "\n" ?>
<?php endforeach; ?>
<?php if (!empty($relations)): ?>
 *
<?php foreach ($relations as $name => $relation): ?>
 * @property-read <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst($name) . ' {@link ' . $className . '::get' . $name  . "()}\n" ?>
<?php endforeach; ?>
<?php endif; ?>
*/
class <?= $className ?> extends <?= '\\' . ltrim($generator->baseClass, '\\') . "\n" ?>
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [<?= empty($rules) ? '' : ("\n            " . implode(",\n            ", $rules) . ",\n        ") ?>];
    }
<?php foreach ($relations as $name => $relation): ?>

    /**
     * @return <?= $relationsClassHints[$name] . "\n" ?>
     */
    public function get<?= $name ?>(): <?= $relationsClassHints[$name] ?>
    {
        <?= $relation[0] . "\n" ?>
    }
<?php endforeach; ?>
<?php if ($queryClassName): ?>
    <?php
    $queryClassFullName = ($generator->ns === $generator->queryNs) ? $queryClassName : '\\' . $generator->queryNs . '\\' . $queryClassName;
    echo "\n";
    ?>
    /**
     * @return <?= "$queryClassFullName\n" ?>
     */
    public static function find(): <?= "$queryClassFullName\n" ?>
    {
        return new <?= $queryClassFullName ?>(get_called_class());
    }
<?php endif; ?>

    /**
     * @return array
     */
    public function attributeLabels(): array
    {
        return [
    <?php foreach ($labels as $name => $label): ?>
        <?= "'$name' => " . $generator->generateString($label) . ",\n" ?>
    <?php endforeach; ?>
    ];
    }

    /**
     * @string
     */
    public function formName(): string
    {
        return '<?= $className ?>';
    }

    /**
     * @string
     */
    public static function tableName(): string
    {
        return '<?= $generator->generateTableName('{{%' . $tableName . '}}') ?>';
    }
}