importFastb.panel.Home = function (config) {
	config = config || {};
	Ext.apply(config, {
		baseCls: 'modx-formpanel',
		layout: 'anchor',
		/*
		 stateful: true,
		 stateId: 'importfastb-panel-home',
		 stateEvents: ['tabchange'],
		 getState:function() {return {activeTab:this.items.indexOf(this.getActiveTab())};},
		 */
		hideMode: 'offsets',
		items: [{
			html: '<h2>' + _('importfastb') + '</h2>',
			cls: '',
			style: {margin: '15px 0'}
		}, {
			xtype: 'modx-tabs',
			defaults: {border: false, autoHeight: true},
			border: true,
			hideMode: 'offsets',
			items: [{
                title: _('export'),
				layout: 'anchor',
				items: [{
					html: _('importfastb_intro_msg'),
					cls: 'panel-desc',
				}, {
					xtype: 'importfastb-export-panel',
					cls: 'main-wrapper',
				}]
			}, {
                title: _('importfastb_import'),
				layout: 'anchor',
				items: [{
                    html: _('importfastb_import_msg'),
                    cls: 'panel-desc',
                }, {
					xtype: 'importfastb-import-panel',
					cls: 'main-wrapper',
				}]
			}]
		}]
	});
	importFastb.panel.Home.superclass.constructor.call(this, config);
};
Ext.extend(importFastb.panel.Home, MODx.Panel);
Ext.reg('importfastb-panel-home', importFastb.panel.Home);
