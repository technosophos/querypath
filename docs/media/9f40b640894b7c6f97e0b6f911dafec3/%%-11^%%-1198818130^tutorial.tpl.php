<?php /* Smarty version 2.6.0, created on 2009-02-03 23:33:02
         compiled from tutorial.tpl */ ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "header.tpl", 'smarty_include_vars' => array('title' => $this->_tpl_vars['title'])));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
  if ($this->_tpl_vars['nav']): ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
<td width="10%" align="left" valign="bottom"><?php if ($this->_tpl_vars['prev']): ?><a href=
"<?php echo $this->_tpl_vars['prev']; ?>
"><?php endif; ?>Prev<?php if ($this->_tpl_vars['prev']): ?></a><?php endif; ?></td>
<td width="80%" align="center" valign="bottom"></td>
<td width="10%" align="right" valign="bottom"><?php if ($this->_tpl_vars['next']): ?><a href=
"<?php echo $this->_tpl_vars['next']; ?>
"><?php endif; ?>Next<?php if ($this->_tpl_vars['next']): ?></a><?php endif; ?></td>
</tr>
</table>
<?php endif;  echo $this->_tpl_vars['contents']; ?>

<?php if ($this->_tpl_vars['nav']): ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
<td width="33%" align="left" valign="top"><?php if ($this->_tpl_vars['prev']): ?><a href="<?php echo $this->_tpl_vars['prev']; ?>
"><?php endif; ?>
Prev<?php if ($this->_tpl_vars['prev']): ?></a><?php endif; ?></td>
<td width="34%" align="center" valign="top"><?php if ($this->_tpl_vars['up']): ?><a href=
"<?php echo $this->_tpl_vars['up']; ?>
">Up</a><?php else: ?>&nbsp;<?php endif; ?></td>
<td width="33%" align="right" valign="top"><?php if ($this->_tpl_vars['next']): ?><a href=
"<?php echo $this->_tpl_vars['next']; ?>
"><?php endif; ?>Next<?php if ($this->_tpl_vars['next']): ?></a><?php endif; ?></td>
</tr>

<tr>
<td width="33%" align="left" valign="top"><?php if ($this->_tpl_vars['prevtitle']):  echo $this->_tpl_vars['prevtitle'];  endif; ?></td>
<td width="34%" align="center" valign="top"><?php if ($this->_tpl_vars['uptitle']):  echo $this->_tpl_vars['uptitle'];  endif; ?></td>
<td width="33%" align="right" valign="top"><?php if ($this->_tpl_vars['nexttitle']):  echo $this->_tpl_vars['nexttitle'];  endif; ?></td>
</tr>
</table>
<?php endif;  $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "footer.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>