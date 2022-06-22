<template>
    <div>
        <widget
                color="green"
                @widgetAction="soldNewAsset"
                :button-text="$tc('phrases.assignAppliance',0)"
                :button="true"
                :title="$tc('phrases.soldAppliances')"
                :button-color="'red'"
        >
            <md-table>
                <md-table-row>
                    <md-table-head>{{ $tc('words.name') }}</md-table-head>
                    <md-table-head>{{ $tc('words.cost') }}</md-table-head>
                    <md-table-head> Down Payment </md-table-head>
                    <md-table-head>{{ $tc('words.rate',1) }}</md-table-head>
                </md-table-row>
                <md-table-row v-for="(item, index) in soldAppliancesList" :key="index" :class="selectedApplianceId === item.id  ? 'selected-row' : ''">
                    <md-table-cell md-label="Name" md-sort-by="name">{{item.asset_type.name}}</md-table-cell>
                    <md-table-cell md-label="Cost" md-sort-by="total_cost">{{moneyFormat(item.total_cost)}} {{ currency }}</md-table-cell>
                    <md-table-cell md-label="Down Payment" md-sort-by="Down Payment">{{moneyFormat(item.down_payment)}} {{currency}} </md-table-cell>
                    <md-table-cell md-label="Rates" md-sort-by="rate_count">
                        {{ item.rate_count}}
                        <div
                            :class="index=== -999?'text-danger':'text-success'"
                            style="cursor: pointer; display:inline-block"

                            @click="showDetails(soldAppliancesList[index].id)"
                        >
                            <md-icon>remove_red_eye</md-icon>
                            {{ $tc('words.detail',1) }}
                        </div>
                    </md-table-cell>
                </md-table-row>
            </md-table>
        </widget>
    </div>
</template>

<script>
import widget from '../../../shared/widget'
import { currency } from '@/mixins/currency'
export default {
    name: 'SoldAppliancesList',
    components: {widget},
    mixins: [currency],
    data (){
        return{
            currency: this.$store.getters['settings/getMainSettings'].currency,
            selectedApplianceId: null,
        }
    },
    props:{
        soldAppliancesList:{
            required:true,
        },
        personId:{
            required: true
        }
    },
    created () {
        this.selectedApplianceId = parseInt(this.$route.params.id)
    },
    methods:{
        soldNewAsset(){
            this.$router.push('/sell-appliance/'+ this.personId)
        },
        showDetails(id){
            this.selectedRow(id)
            this.$router.push({ path:'/sold-appliance-detail/' + id}).catch(err => err)

        },
        selectedRow(id){
            if (this.selectedApplianceId !== id) {
                this.selectedApplianceId = id
            }

        },
    }

}
</script>

<style scoped>
.selected-row{
    background-color: #ccc
}
</style>
