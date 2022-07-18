<template>
    <div>
        <widget
            color="green"
            title="Settings"
        >
            <div style="padding: 2vh;">
                <md-tabs>
                    <md-tab id="tab-home" md-icon="home" md-label="Main" exact>
                        <main-settings :mainSettings="mainSettings"/>
                    </md-tab>

                    <md-tab id="tab-sms" name="sms" md-icon="sms" md-label="Sms">
                        <sms-settings/>
                    </md-tab>

                    <md-tab id="tab-map" md-icon="map" md-label="Map">
                        <map-settings :center="center" :mapSettings="mapSettings"/>
                    </md-tab>
                </md-tabs>
            </div>


        </widget>

    </div>
</template>

<script>
import Widget from '../../shared/widget'
import MainSettings from './MainSettings'
import MapSettings from './MapSettings'
import TicketSettings from './TicketSettings'
import SmsSettings from './SmsSettings'
import MailSettings from './MailSettings'
import { MainSettingsService } from '@/services/MainSettingsService'
import { MapSettingsService } from '@/services/MapSettingsService'
import { TicketSettingsService } from '@/services/TicketSettingsService'

import { mapGetters } from 'vuex'

export default {
    name: 'Settings',
    components: { Widget, MainSettings, MapSettings, TicketSettings, SmsSettings, MailSettings },
    data () {
        return {
            mainSettingsService: new MainSettingsService(),
            mapSettingService: new MapSettingsService(),
            ticketSettingsService: new TicketSettingsService(),
            ticketSettings: {},
            mapSettings: {},
            mainSettings: {},
            center: null,
            smsBodies: []
        }
    },
    mounted () {
        this.getSettingStates()
    },
    methods: {
        async getSettingStates () {
            this.mainSettings = this.$store.getters['settings/getMainSettings']
            this.mapSettings = this.$store.getters['settings/getMapSettings']
            this.center = [this.mapSettings.latitude, this.mapSettings.longitude]
            this.ticketSettings = this.$store.getters['settings/getTicketSettings']
        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message
            })
        },
    },

}
</script>

<style scoped>

</style>
