<template>
    <div>
        <div class="md-layout md-gutter ">
            <div class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100">
                <box
                    :centerText="true"
                    :color="[ '#26c6da','#00acc1']"
                    :subText="clusters.length.toString()"
                    :headerTextColor="'#dddddd'"
                    :headerText="$tc('phrases.registeredClusters')"
                    :subTextColor="'#e3e3e3'"
                    :boxIcon="'map'"
                    :boxIconColor="'#578839'"

                />
            </div>
            <div class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100">

                <box
                    :centerText="true"
                    :color="[ '#ffa726','#fb8c00']"
                    :subText="readable(population).toString()"
                    :headerTextColor="'#dddddd'"
                    :headerText="$tc('phrases.registeredCustomers')"
                    :subTextColor="'#e3e3e3'"
                    :boxIcon="'supervisor_account'"
                    :boxIconColor="'#385a76'"

                />
            </div>
            <div class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100">
                <box
                    :centerText="true"
                    :color="[ '#ef5350','#e53935']"
                    :subText="readable(connections).toString()"
                    :headerTextColor="'#dddddd'"
                    :header-text="$tc('phrases.connectedMeters')"
                    :subTextColor="'#e3e3e3'"
                    boxIcon="settings_input_hdmi"
                    :boxIconColor="'#604058'"
                />
            </div>
            <div class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100">
                <box
                    :centerText="true"
                    :color="[ '#6eaa44','#578839']"
                    :sub-text="readable(revenue).toString() + $store.getters['settings/getMainSettings'].currency"
                    :headerTextColor="'#dddddd'"
                    :header-text="$tc('words.revenue') +' ('+ $tc('phrases.lastXDays',1,{x: 30})+ ')' "
                    :subTextColor="'#e3e3e3'"
                    :boxIcon="'attach_money'"
                    :boxIconColor="'#5c5837'"
                />
            </div>
        </div>

    </div>
</template>

<script>
import Box from '../Box'
import { currency } from '../../mixins/currency'

export default {
    name: 'BoxGroup',
    components: { Box },
    mixins: [currency],
    props: {
        clusters: {
            type: Array,
            required: true,
        },
    },

    computed: {
        population () {
            let population = 0
            for (let c in this.clusters) {
                population += this.clusters[c].population

            }
            return population
        },

        connections () {
            let connections = 0
            for (let c in this.clusters) {
                connections += this.clusters[c].meterCount
            }
            return connections
        },
        revenue () {
            let revenue = 0
            for (let c in this.clusters) {
                revenue += parseInt(this.clusters[c].revenue)

            }
            return revenue
        },
    },
    methods: {
        newCluster () {
            this.$router.push('/clusters/add')
        },
    },
}
</script>

<style>
    .box-group {
        display: flex;
        margin-top: 1rem;
    }

    .btn-log {
        background-color: #689f38 !important;

        color: white !important;
        width: 100%;
    }
</style>
