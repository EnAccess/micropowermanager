<template>
<div class="md-layout md-gutter md-size-100">
    <div class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-33">
        <box
            :center-text="true"
            :color="[ '#ffa726','#fb8c00']"
            :header-text="$tc('phrases.soldEnergy')"
            :header-text-color="'#dddddd'"
            :sub-text="soldEnergy.toString() +'kWh'"
            :sub-text-color="'#e3e3e3'"
            box-icon="wb_iridescent"
            :box-icon-color="'#578839'"
        />
    </div>
    <div class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-33">
        <box v-if="currentTransaction"

             :center-text="true"
             :color="[ '#ef5350','#e53935']"
             :header-text="$tc('phrases.processedTransactions')"
             :header-text-color="'#dddddd'"
             :sub-text="readable(currentTransaction[0].amount).toString() "
             :sub-text-color="'#e3e3e3'"
             box-icon="list"
             :box-icon-color="'#578839'"
        />
    </div>
    <div class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-33">
        <box v-if="currentTransaction"

             :center-text="true"
             :color="[ '#6eaa44','#578839']"
             :header-text="$tc('words.revenue')"
             :header-text-color="'#dddddd'"
             :sub-text="readable(currentTransaction[0].revenue).toString() + $store.getters['settings/getMainSettings'].currency"
             :sub-text-color="'#e3e3e3'"
             box-icon="attach_money"
             :box-icon-color="'#578839'"
        />
    </div>
</div>
</template>

<script>
import { MiniGridService } from '../../services/MiniGridService'
import Box from '../Box'
import { currency } from '../../mixins/currency'

export default {
    name: 'BoxGroup',
    components: { Box },
    mixins: [currency],
    props:{
        miniGridId: {
            required: true,
        }
    },
    data(){
        return{
            miniGridService: new MiniGridService(),
            soldEnergy: 0,
            currentTransaction: null,
        }
    },
    methods:{
        async getTransactionsOverview (startDate, endDate) {
            try {
                this.currentTransaction = await this.miniGridService.getTransactionsOverview(this.miniGridId, startDate, endDate)
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        async getSoldEnergy (startDate, endDate) {
            try {
                this.soldEnergy = await this.miniGridService.getSoldEnergy(this.miniGridId ,startDate, endDate)
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message,
                speed: 0
            })
        },
    }

}
</script>

<style scoped>

</style>
