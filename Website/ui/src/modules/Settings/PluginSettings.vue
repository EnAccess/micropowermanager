<template>
    <div>
        <div class="md-layout md-gutter">
            <div v-for="plugin in plugins" :key=plugin.id class="box md-layout-item  md-size-25 md-small-size-50">
                <div class="header-text">{{ plugin.name }}</div>
                <small class="sub-text">{{ plugin.description }}</small>
                <md-switch v-model="plugin.checked" @change="onSwitchChange($event,plugin)" class="data-stream-switch"
                           :disabled="switching"/>
            </div>
        </div>
        <md-progress-bar md-mode="indeterminate" v-if="progressing"/>
    </div>
</template>

<script>

import { MpmPluginService } from '@/services/MpmPluginService'
import { PluginService } from '@/services/PluginService'
import { EventBus } from '@/shared/eventbus'

export default {
    name: 'PluginSettings',
    data () {
        return {
            mpmPluginsService: new MpmPluginService(),
            pluginService: new PluginService(),
            progressing: false,
            switching: false,
        }
    },
    props: {
        plugins: {
            type: Array,
            required: true
        }
    },
    methods: {
        async onSwitchChange (event, plugin) {
            this.switching = true
            this.progressing = true
            try {
                await this.pluginService.updatePlugin(plugin)
                EventBus.$emit('setSidebar')
                this.alertNotify('success', 'Plugin updated successfully')
            }catch (e) {
                this.switching = false
                this.alertNotify('error', e.message)
            }

            this.switching = false
            this.progressing = false
        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message
            })
        }
    }
}
</script>

<style scoped lang="scss">
.box {
    border-radius: 5px;
    padding: 1.3vw;
    margin-top: 1vh;
    box-shadow: 0 1px 5px -2px rgb(53 53 53 / 30%), 0 0px 4px 0 rgb(0 0 0 / 12%), 0 0px 0px -5px #8e8e8e
}

.header-text {
    color: rgb(148, 148, 148);
    margin-top: 0px;
    margin-bottom: 1rem;
    font-size: 1.2rem;
    font-weight: bold;
}

.sub-text {
    font-weight: 400;
    font-size: 0.7rem;
}

.stepper-title {
    text-align: center !important;
    font-size: large !important;
    padding: 1rem 1rem 0 1rem;
    margin-bottom: 3rem !important;
    font-weight: bolder !important;
}

.md-steppers-navigation {
    box-shadow: none;
    display: flex;
    border-bottom: 1px solid #bbb;
}

.data-stream-switch {
    margin-left: 3rem !important;
    float: right;
}
</style>