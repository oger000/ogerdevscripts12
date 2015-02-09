<?PHP

$extAliases = array(
	'association.belongsto',
	'association.hasmany',
	'association.hasone',
	'axis.category',
	'axis.gauge',
	'axis.numeric',
	'axis.radial',
	'axis.time',
	'data.field',
	'data.tree',
	'direct.event',
	'direct.exception',
	'direct.jsonprovider',
	'direct.pollingprovider',
	'direct.provider',
	'direct.remotingprovider',
	'direct.rpc',
	'direct.transaction',
	'editing.editing',
	'feature.abstractsummary',
	'feature.feature',
	'feature.grouping',
	'feature.groupingsummary',
	'feature.rowbody',
	'feature.rowwrap',
	'feature.summary',
	'formaction.directload',
	'formaction.directsubmit',
	'formaction.load',
	'formaction.standardsubmit',
	'formaction.submit',
	'idgen.sequential',
	'idgen.uuid',
	'layout.absolute',
	'layout.accordion',
	'layout.accordion',
	'layout.anchor',
	'layout.autocomponent',
	'layout.auto', 'layout.autocontainer',
	'layout.auto', 'layout.autocontainer',
	'layout.body',
	'layout.body',
	'layout.border',
	'layout.boundlist',
	'layout.box',
	'layout.box',
	'layout.button',
	'layout.button',
	'layout.card',
	'layout.checkboxgroup',
	'layout.checkboxgroup',
	'layout.column',
	'layout.column',
	'layout.columncomponent',
	'layout.combobox',
	'layout.container',
	'layout.container',
	'layout.dock',
	'layout.draw',
	'layout.editor',
	'layout.field',
	'layout.fieldcontainer',
	'layout.fieldset',
	'layout.fieldset',
	'layout.fit',
	'layout.form',
	'layout.gridcolumn',
	'layout.hbox',
	'layout.hbox',
	'layout.htmleditor',
	'layout.htmleditor',
	'layout.progressbar',
	'layout.progressbar',
	'layout.sliderfield',
	'layout.sliderfield',
	'layout.table',
	'layout.table',
	'layout.tableview',
	'layout.tableview',
	'layout.textareafield',
	'layout.textfield',
	'layout.triggerfield',
	'layout.vbox',
	'layout.vbox',
	'plugin.bufferedrenderer',
	'plugin.cellediting',
	'plugin.divrenderer',
	'plugin.gridheaderreorderer',
	'plugin.gridheaderresizer',
	'plugin.gridviewdragdrop',
	'plugin.rowediting',
	'plugin.rowexpander',
	'plugin.treeviewdragdrop',
	'proxy.ajax',
	'proxy.direct',
	'proxy.jsonp', 'proxy.scripttag',
	'proxy.jsonp', 'proxy.scripttag',
	'proxy.localstorage',
	'proxy.memory',
	'proxy.proxy',
	'proxy.rest',
	'proxy.server',
	'proxy.sessionstorage',
	'reader.array',
	'reader.json',
	'reader.xml',
	'selection.cellmodel',
	'selection.checkboxmodel',
	'selection.rowmodel',
	'selection.treemodel',
	'series.area',
	'series.bar',
	'series.column',
	'series.gauge',
	'series.line',
	'series.pie',
	'series.radar',
	'series.scatter',
	'state.localstorage',
	'store.array',
	'store.buffer',
	'store.direct',
	'store.json',
	'store.jsonp',
	'store.node',
	'store.store',
	'store.tree',
	'store.xml',
	'widget.actioncolumn',
	'widget.actioncolumn',
	'widget.booleancolumn',
	'widget.booleancolumn',
	'widget.bordersplitter',
	'widget.boundlist',
	'widget.button',
	'widget.buttongroup',
	'widget.chart',
	'widget.checkbox',
	'widget.checkboxfield', 'widget.checkbox',
	'widget.checkboxfield', 'widget.checkbox',
	'widget.checkboxgroup',
	'widget.checkcolumn',
	'widget.colormenu',
	'widget.colorpicker',
	'widget.colorpicker',
	'widget.combobox', 'widget.combo',
	'widget.combobox', 'widget.combo',
	'widget.component',
	'widget.component', 'widget.box',
	'widget.component', 'widget.box',
	'widget.container',
	'widget.cycle',
	'widget.dataview',
	'widget.datecolumn',
	'widget.datecolumn',
	'widget.datefield',
	'widget.datemenu',
	'widget.datepicker',
	'widget.datepicker',
	'widget.displayfield',
	'widget.draw',
	'widget.editor',
	'widget.field',
	'widget.fieldcontainer',
	'widget.fieldset',
	'widget.filebutton',
	'widget.filefield', 'widget.fileuploadfield',
	'widget.filefield', 'widget.fileuploadfield',
	'widget.flash',
	'widget.form',
	'widget.gridcolumn',
	'widget.gridpanel', 'widget.grid',
	'widget.gridpanel', 'widget.grid',
	'widget.gridview',
	'widget.header',
	'widget.headercontainer',
	'widget.hiddenfield', 'widget.hidden',
	'widget.hiddenfield', 'widget.hidden',
	'widget.htmleditor',
	'widget.image', 'widget.imagecomponent',
	'widget.image', 'widget.imagecomponent',
	'widget.label',
	'widget.loadmask',
	'widget.menu',
	'widget.menucheckitem',
	'widget.menuitem',
	'widget.menuseparator',
	'widget.messagebox',
	'widget.monthpicker',
	'widget.multislider',
	'widget.numbercolumn',
	'widget.numbercolumn',
	'widget.numberfield',
	'widget.pagingtoolbar',
	'widget.panel',
	'widget.pickerfield',
	'widget.progressbar',
	'widget.propertygrid',
	'widget.quicktip',
	'widget.radiofield', 'widget.radio',
	'widget.radiofield', 'widget.radio',
	'widget.radiogroup',
	'widget.roweditor',
	'widget.roweditorbuttons',
	'widget.rownumberer',
	'widget.slidertip',
	'widget.slider', 'widget.sliderfield',
	'widget.slider', 'widget.sliderfield',
	'widget.spinnerfield',
	'widget.splitbutton',
	'widget.splitter',
	'widget.tab',
	'widget.tabbar',
	'widget.tablepanel',
	'widget.tableview',
	'widget.tabpanel',
	'widget.tbfill',
	'widget.tbitem',
	'widget.tbseparator',
	'widget.tbspacer',
	'widget.tbtext',
	'widget.templatecolumn',
	'widget.templatecolumn',
	'widget.text',
	'widget.textareafield', 'widget.textarea',
	'widget.textareafield', 'widget.textarea',
	'widget.textfield',
	'widget.timefield',
	'widget.timepicker',
	'widget.tool',
	'widget.toolbar',
	'widget.tooltip',
	'widget.treecolumn',
	'widget.treepanel',
	'widget.treeview',
	'widget.triggerfield', 'widget.trigger',
	'widget.triggerfield', 'widget.trigger',
	'widget.viewport',
	'widget.window',
	'writer.base',
	'writer.json',
	'writer.xml',

);


?>

