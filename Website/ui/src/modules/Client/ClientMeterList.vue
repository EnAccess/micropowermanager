<template>

    <div>
        <widget
            :title="$tc('words.meter',2)"
            color="green"
            :subscriber="subscriber"
        >
            <div class="md-layout md-gutter">
                <div class="md-layout-item md-medium-size-100 md-large-size-100 md-small-size-100">
                    <md-table style="width:100%" v-model="this.meters" md-card md-fixed-header>
                        <md-table-row slot="md-table-row" slot-scope="{ item }">
                            <md-table-cell md-label="#" >
                                <md-icon @click="setMapCenter(meter.id)" style="cursor:pointer;">place</md-icon>
                            </md-table-cell>
                            <md-table-cell :md-label="$tc('words.id')" md-sort-by="id">{{ item.id }}</md-table-cell>
                            <md-table-cell :md-label="$tc('phrases.serialNumber')" md-sort-by="serial_number">{{ item.serial_number }}
                            </md-table-cell>
                            <md-table-cell :md-label="$tc('phrases.maxCurrent')" md-sort-by="max_current">{{ item.max_current }}
                            </md-table-cell>
                            <md-table-cell :md-label="$tc('words.phase')" md-sort-by="phase">{{ item.phase }}</md-table-cell>
                            <md-table-cell :md-label="$tc('words.tariff')" md-sort-by="tariff.name">{{ item.tariff.name}}
                                {{item.tariff.price}}
                            </md-table-cell>
                        </md-table-row>
                    </md-table>
                </div>
            </div>

        </widget>
    </div>

</template>

<script>
import { Meters } from '@/classes/person/meters'
import { EventBus } from '@/shared/eventbus'
import Widget from '../../shared/widget'

export default {
    name: 'ClientMeterList',
    props: {
        'meterList': {
            required: true,
        }
    },
    components: {
        Widget
    },
    data () {
        return {
            meter: new Meters(),
            meters: [],
            subscriber:'client-meter-list'
        }
    },
    mounted: function () {
        for (let m in this.meterList) {
            this.getDetail(this.meterList[m])
        }
        EventBus.$emit('widgetContentLoaded',this.subscriber,this.meterList.length)

    },
    methods: {
        getDetail (meterId) {
            this.meter.getMeterDetails(meterId).then((meter) => {
                this.meters.push(meter)
            })

        },
        setMapCenter (meterId) {
            EventBus.$emit('map', meterId)
        }
    },

}
</script>

<style>

    .meter-list {
        cursor: pointer;
    }
</style>
