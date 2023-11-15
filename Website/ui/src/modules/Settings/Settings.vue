<template>
    <div>
        <widget
            color="green"
            title="Settings"
        >

            <md-tabs>
                <md-tab id="tab-home" md-icon="home" md-label="Main">
                    <main-settings :mainSettings="mainSettings"/>
                </md-tab>
                <md-tab id="tab-plugin" md-icon="widgets" md-label="Plugins">
                    <plugin-settings :plugins="plugins"/>
                </md-tab>
                <md-tab id="tab-sms" name="sms" md-icon="sms" md-label="Sms">
                    <sms-settings/>
                </md-tab>

                <md-tab id="tab-map" md-icon="map" md-label="Map">
                    <map-settings :mapSettings="mapSettings"/>
                </md-tab>
            </md-tabs>


        </widget>

    </div>
</template>

<script>
import Widget from '@/shared/widget'
import MainSettings from './MainSettings'
import MapSettings from './MapSettings'
import TicketSettings from './TicketSettings'
import SmsSettings from './SmsSettings'
import MailSettings from './MailSettings'
import { MainSettingsService } from '@/services/MainSettingsService'
import { MapSettingsService } from '@/services/MapSettingsService'
import { TicketSettingsService } from '@/services/TicketSettingsService'

import PluginSettings from '@/modules/Settings/PluginSettings'
import { MpmPluginService } from '@/services/MpmPluginService'
import { PluginService } from '@/services/PluginService'
import { notify } from '@/mixins'

export default {
    name: 'Settings',
    mixins: [notify],
    components: { PluginSettings, Widget, MainSettings, MapSettings, TicketSettings, SmsSettings, MailSettings },
    data () {
        return {
            mainSettingsService: new MainSettingsService(),
            mapSettingService: new MapSettingsService(),
            ticketSettingsService: new TicketSettingsService(),
            mpmPluginsService: new MpmPluginService(),
            pluginsService: new PluginService(),
            ticketSettings: {},
            mapSettings: {},
            mainSettings: {},
            center: null,
            smsBodies: [],
            plugins: [],
        }
    },
    mounted () {
        this.getSettingStates()
        this.getPlugins()
    },
    methods: {
        async getSettingStates () {
            this.mainSettings = this.$store.getters['settings/getMainSettings']
            this.mapSettings = this.$store.getters['settings/getMapSettings']
        },
        async getPlugins () {
            const mpmPlugins = await this.mpmPluginsService.getMpmPlugins()
            const plugins = await this.pluginsService.getPlugins()

            this.plugins = mpmPlugins.map(plugin => {
                let foundPlugin = plugins.find(p => p.mpm_plugin_id === plugin.id)
                return {
                    id: plugin.id,
                    name: plugin.name,
                    description: plugin.description,
                    checked: foundPlugin && foundPlugin.status === 1
                }
            })
        },
    },
}
</script>


