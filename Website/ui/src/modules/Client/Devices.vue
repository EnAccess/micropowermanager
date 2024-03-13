<template>
    <div>
        <widget
            :title="$tc('words.devices')"
            color="green"
            :subscriber="subscriber"
        >
            <div class="md-layout md-gutter">
                <div
                    class="md-layout-item md-medium-size-100 md-large-size-100 md-small-size-100"
                >
                    <md-table
                        style="width: 100%"
                        v-model="this.devices"
                        md-card
                        md-fixed-header
                    >
                        <md-table-row slot="md-table-row" slot-scope="{ item }">
                            <md-table-cell md-label="#">
                                <md-icon
                                    @click="setMapCenter(item.id)"
                                    style="cursor: pointer"
                                >
                                    place
                                </md-icon>
                            </md-table-cell>
                            <md-table-cell
                                :md-label="$tc('phrases.serialNumber')"
                                md-sort-by="device_serial"
                            >
                                {{ item.device_serial }}
                            </md-table-cell>
                            <md-table-cell
                                :md-label="$tc('words.deviceType')"
                                md-sort-by="device_type"
                            >
                                {{ $tc(`words.${item.device_type}`) }}
                            </md-table-cell>
                        </md-table-row>
                    </md-table>
                </div>
            </div>
        </widget>
    </div>
</template>

<script>
import Widget from '@/shared/widget.vue'
import { EventBus } from '@/shared/eventbus'

export default {
    name: 'Devices',
    props: {
        devices: {
            required: true,
            type: Array,
        },
    },
    components: {
        Widget,
    },
    data() {
        return {
            subscriber: 'client-device-list',
        }
    },
    mounted: function () {
        EventBus.$emit(
            'widgetContentLoaded',
            this.subscriber,
            this.devices.length,
        )
    },
    methods: {
        setMapCenter(device) {
            EventBus.$emit('setMapCenterForDevice', device)
        },
    },
}
</script>
