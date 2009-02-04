<?php /* Smarty version 2.6.0, created on 2009-02-03 23:33:02
         compiled from tutorial_tree.tpl */ ?>
<?php require_once(SMARTY_DIR . 'core' . DIRECTORY_SEPARATOR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'strip_tags', 'tutorial_tree.tpl', 2, false),)), $this); ?>
<ul>
	<li type="square"><a href="<?php echo $this->_tpl_vars['main']['link']; ?>
"><?php echo ((is_array($_tmp=$this->_tpl_vars['main']['title'])) ? $this->_run_mod_handler('strip_tags', true, $_tmp) : smarty_modifier_strip_tags($_tmp)); ?>
</a>
<?php if ($this->_tpl_vars['kids']):  echo $this->_tpl_vars['kids']; ?>
</li><?php endif; ?>
</ul>
