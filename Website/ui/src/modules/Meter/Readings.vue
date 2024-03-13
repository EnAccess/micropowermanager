<template>
    <div>
        <widget :title="$tc('phrases.meterReadings')"
                :id="'meter-readings'">
            <div role="menu" slot="tabbar">
                <button
                    class="md-button dropdown-toggle btn-xs"
                    aria-expanded="false"
                    id="datepicker-button-trigger"
                    style="color:white"
                >
                    Period
                    <md-icon>calendar_today</md-icon>
                </button>
            </div>

            <md-card>
                <md-card-content>
                    <div v-if="chartData.length>0">
                        <GChart type="LineChart" :data="chartData" :options="chartOptions"></GChart>
                    </div>

                    <div v-if="chartData.length===0 && loading === false" class="text-center">
                        <h2>{{ $tc('phrases.noData') }} {{ dates.dateOne }} - {{ dates.dateTwo }}</h2>
                    </div>

                </md-card-content>
            </md-card>
        </widget>

        <airbnb-style-datepicker
            :trigger-element-id="'datepicker-button-trigger'"
            :mode="'range'"
            :date-one="dates.dateOne"
            :date-two="dates.dateTwo"
            :min-date="'2018-01-01'"
            :endDate="dates.today"
            :fullscreen-mobile="true"
            :months-to-show="2"
            :offset-y="500"
            v-on:date-one-selected="(val) => { dates.dateOne = val }"
            v-on:date-two-selected="(val) => { dates.dateTwo = val }"
            @apply="getConsumptions"
        ></airbnb-style-datepicker>
    </div>

</template>

<script>
import Widget from '../../shared/widget'
import moment from 'moment'
import { Consumptions } from '@/classes/meter/Consumptions'
import { currency } from '@/mixins/currency'
export default {
    name: 'Readings.vue',
    components:{ Widget },
    mixins: [currency],
    props:{
        meter:{
            type:Object
        },
    },
    data(){
        return{
            chartData: [],
            chartOptions: {
                chart: {
                    title: 'Company Performance',
                    subtitle: 'Sales, Expenses, and Profit: 2014-2017'
                },
                height: 400,
                colors: ['#1b9e77', '#d95f02', '#7570b3']
            },
            dates: {
                dateTwo: null,
                dateOne: null,
                today: null,
                difference: 0
            },
            loading: true,
            consumptions:null,
        }
    },
    created () {
        //initialize dates
        let baseDate = moment()
        this.dates.today = baseDate.format('YYYY-MM-DD')
        this.dates.dateTwo = baseDate.add(-1, 'days').format('YYYY-MM-DD')
        this.dates.dateOne = baseDate.add(-1, 'weeks').format('YYYY-MM-DD')
    },
    mounted () {
        this.consumptions = new Consumptions(this.$route.params.id)
        this.getConsumptions()
    },
    methods:{
        getConsumptions () {
            this.loading = true
            this.chartData = []
            this.consumptions
                .getData(this.dates.dateOne, this.dates.dateTwo)
                .then(() => {
                    this.loading = false
                    if (this.consumptions.data.length === 0) {
                        this.chartData = []
                        return
                    }
                    this.chartData.push([this.$tc('words.date'), this.$tc('words.consumption'), this.$tc('words.credit')])
                    this.chartData = this.chartData.concat(this.consumptions.data)
                })
        },
    }
}
</script>

<style scoped>
</style>
