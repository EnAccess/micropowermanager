<template>

    <div class="md-layout md-gutter">

        <div class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-25 small-size-style">
            <box
                :center-text="true"
                :color="[ '#26c6da','#00acc1']"
                :header-text="$tc('words.miniGrid')"
                :header-text-color="'#dddddd'"
                :sub-text="cluster.mini_grids.length.toString()"
                :sub-text-color="'#e3e3e3'"
                :box-icon="'map'"
                :box-icon-color="'#578839'"
            />
        </div>
        <div class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-25 small-size-style">
            <box
                :center-text="true"
                :color="[ '#ffa726','#fb8c00']"
                :sub-text="cluster.population.toString()"
                :header-text-color="'#dddddd'"
                :header-text="$tc('words.people')"
                :sub-text-color="'#e3e3e3'"
                :box-icon="'supervisor_account'"
                :box-icon-color="'#385a76'"

            />
        </div>
        <div class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-25 small-size-style">
            <box
                :center-text="true"
                :color="[ '#ef5350','#e53935']"
                :sub-text="cluster.meterCount.toString()"
                :header-text-color="'#dddddd'"
                :header-text="$tc('phrases.connectedMeters')"
                :sub-text-color="'#e3e3e3'"
                :box-icon="'settings_input_hdmi'"
                :box-icon-color="'#604058'"
            />
        </div>
        <div class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-25 small-size-style">
            <box
                v-if="cluster.revenue.toString()"
                :center-text="true"
                :color="[ '#6eaa44','#578839']"
                :sub-text="readable(cluster.revenue) + $store.getters['settings/getMainSettings'].currency"
                :header-text-color="'#dddddd'"
                :header-text="$tc('words.revenue') +' ('+ $tc('phrases.lastXDays',1,{x: 30})+ ')' "
                :sub-text-color="'#e3e3e3'"
                :box-icon="'attach_money'"
                :box-icon-color="'#5c5837'"
            />
        </div>
    </div>

</template>

<script>
import Box from '../Box'
import { currency } from '@/mixins/currency'

export default {
    name: 'BoxGroup',
    components: { Box },
    mixins: [currency],
    props: {
        cluster: {
            type: Object,
            required: true,
        },

    },
    data: () => ({
        boxData: [],
    })
}
</script>

<style>
@media screen and (max-width: 1280px) {
    .small-size-style {
        margin-bottom: 1rem !important;
        min-height: unset;
    }
}

</style>
