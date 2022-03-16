<template>
    <div>
        <h2>Ticket Settings ( {{ticketSettingsService.ticketSettings.name}} )</h2>
        <div class="md-layout md-size-100">
            <md-field :class="{'md-invalid': errors.has('api_token')}">
                <label> API Token</label>
                <md-input
                        name="api_token"
                        id="api_token"
                        v-validate="'required'"
                        v-model="ticketSettingsService.ticketSettings.apiToken"></md-input>
            </md-field>
            <span class="md-error">{{ errors.first('api_token') }}</span>
        </div>
        <div class="md-layout md-size-100">
            <md-field :class="{'md-invalid': errors.has('api_key')}">
                <label for="api_key">API Key</label>
                <md-input
                        name="api_key"
                        id="api_key"
                        v-validate="'required'"
                        v-model="ticketSettingsService.ticketSettings.apiKey"></md-input>
            </md-field>
            <span class="md-error">{{ errors.first('api_key') }}</span>
        </div>
        <div class="md-layout md-size-100">
            <md-field :class="{'md-invalid': errors.has('api_url')}">
                <label>API Url</label>
                <md-input
                        name="api_url"
                        id="api_url"
                        v-validate="'required'"
                        v-model="ticketSettingsService.ticketSettings.apiUrl"></md-input>
            </md-field>
            <span class="md-error">{{ errors.first('api_url') }}</span>
        </div>
        <div class="md-layout md-alignment-bottom-right">
            <md-button class="md-primary md-dense md-raised" @click="updateTicketSettings">Save</md-button>
        </div>
        <md-progress-bar v-if="progress" md-mode="indeterminate"></md-progress-bar>
    </div>
</template>

<script>

import { TicketSettingsService } from '../../services/TicketSettingsService'

export default {
    name: 'TicketSettings',
    props: {
        ticketSettings: {
            type: Object
        }
    },
    data () {
        return {
            ticketSettingsService: new TicketSettingsService(),
            progress: false,
        }
    },
    mounted () {
        this.fetchTicketSettings()
    },
    methods: {
        fetchTicketSettings () {
            this.ticketSettingsService.ticketSettings = this.ticketSettings
        },
        async updateTicketSettings () {
            this.progress = true
            let validator = await this.$validator.validateAll()
            if (!validator) {
                return
            }
            try {
                await this.ticketSettingsService.update()
                this.$store.dispatch('settings/setTicketSettings', this.ticketSettingsService.ticketSettings).then(() => {

                }).catch((err) => {
                    console.log(err)
                })
                this.alertNotify('success', 'Updated Successfully')
            } catch (e) {
                this.alertNotify('error', e.message)
            }
            this.progress = false
        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message
            })
        },
    }
}
</script>

<style scoped>

</style>
