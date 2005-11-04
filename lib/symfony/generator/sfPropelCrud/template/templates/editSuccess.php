[?php use_helper('Object') ?]

[?php echo form_tag('<?php echo $moduleName ?>/update') ?]

<?php foreach ($this->getPrimaryKey() as $pk): ?>
[?php echo object_input_hidden_tag($object, 'get<?php echo $pk->getPhpName() ?>') ?]
<?php endforeach ?>

<table>
<tbody>
<?php foreach ($this->tableMap->getColumns() as $column): ?>
<?php if ($column->isPrimaryKey()) continue ?>
<tr>
<th><?php echo $column->getPhpName() ?><?php if ($column->isNotNull()): ?>*<?php endif ?>:</th>
<td>[?php echo <?php
  $type = $column->getCreoleType();
  if ($column->isForeignKey())
  {
    $relatedTable = $this->map->getDatabaseMap()->getTable($column->getRelatedTableName()); 
    echo "object_select_tag(\$object, 'get{$column->getPhpName()}', array('related_class' => '{$relatedTable->getPhpName()}'))";
  }
  else if ($type == CreoleTypes::DATE)
  {
    // rich=false not yet implemented
    echo "object_input_date_tag(\$object, 'get{$column->getPhpName()}', array('rich' => true))";
  }
  else if ($type == CreoleTypes::BOOLEAN)
  {
    echo "object_checkbox_tag(\$object, 'get{$column->getPhpName()}')";
  }
  else if ($type == CreoleTypes::CHAR || $type == CreoleTypes::VARCHAR || $type == CreoleTypes::LONGVARCHAR)
  {
    $size = ($column->getSize() > 20 ? ($column->getSize() < 80 ? $column->getSize() : 80) : 20);
    echo "object_input_tag(\$object, 'get{$column->getPhpName()}', array('size' => $size))";
  }
  else if ($type == CreoleTypes::INTEGER || $type == CreoleTypes::TINYINT || $type == CreoleTypes::SMALLINT || $type == CreoleTypes::BIGINT)
  {
    echo "object_input_tag(\$object, 'get{$column->getPhpName()}', array('size' => 7))";
  }
  else if ($type == CreoleTypes::FLOAT || $type == CreoleTypes::DOUBLE || $type == CreoleTypes::DECIMAL || $type == CreoleTypes::NUMERIC || $type == CreoleTypes::REAL)
  {
    echo "object_input_tag(\$object, 'get{$column->getPhpName()}', array('size' => 7))";
  }
  else if ($type == CreoleTypes::TEXT || $type == CreoleTypes::LONGVARCHAR)
  {
    echo "object_textarea_tag(\$object, 'get{$column->getPhpName()}')";
  }
  else
  {
    echo "object_input_tag(\$object, 'get{$column->getPhpName()}', array('disabled' => true))";
  }
  ?>
  ?]</td>
</tr>
<?php endforeach ?>
</tbody>
</table>
<hr />
[?php echo submit_tag('save') ?]
[?php if (<?php echo $this->getPrimaryKeyIsSet() ?>): ?]
  &nbsp;[?php echo link_to('delete', '<?php echo $moduleName ?>/delete?<?php echo $this->getPrimaryKeyUrlParams() ?>, 'post=true') ?]
  &nbsp;[?php echo link_to('cancel', '<?php echo $moduleName ?>/show?<?php echo $this->getPrimaryKeyUrlParams() ?>) ?]
[?php else: ?]
  &nbsp;[?php echo link_to('cancel', '<?php echo $moduleName ?>/list') ?]
[?php endif ?]
</form>
