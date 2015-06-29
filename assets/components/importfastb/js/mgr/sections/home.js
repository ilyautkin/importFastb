importFastb.page.Home = function (config) {
	config = config || {};
	Ext.applyIf(config, {
		components: [{
			xtype: 'importfastb-panel-home', renderTo: 'importfastb-panel-home-div'
		}]
	});
	importFastb.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(importFastb.page.Home, MODx.Component);
Ext.reg('importfastb-page-home', importFastb.page.Home);