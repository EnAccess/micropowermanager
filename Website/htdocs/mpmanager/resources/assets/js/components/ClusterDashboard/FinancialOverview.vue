<template>
    <widget
        :title="$tc('phrases.financeOverview',0, {period: periodText})"
        :id="'clusters-finance-overview'"
        button
        button-text="Set Period"
        button-color="red"
        @widgetAction="showPeriod"
        button-icon="calendar_today"

    >
        <div v-if="setPeriod" class="period-selector">
            <p>{{ $tc('phrases.selectPeriod') }}</p>
            <div class="md-layout md-gutter">
                <div class="md-layout-item md-size-100">
                    <md-datepicker v-model="period.from" md-immediately>
                        <label>{{ $tc('phrases.fromDate') }}</label>
                    </md-datepicker>
                </div>
                <div class="md-layout-item md-size-100">
                    <md-datepicker v-model="period.to" md-immediately>
                        <label>{{ $tc('phrases.toDate') }}</label>
                    </md-datepicker>
                </div>
            </div>


            <div style="margin-top: 5px;">
                <md-progress-bar md-mode="indeterminate" v-if="loading"/>
                <button style="width:100%;" class="btn btn-primary" v-if="!loading" @click="getClusterFinancialData">
                    {{ $tc('words.send') }}
                </button>
            </div>
        </div>

        <div class="md-layout md-gutter" style="padding: 10px">
            <!-- donut chart-->
            <div class="md-layout-item md-size-35 md-medium-size-100"
                 :class="lineChartFullScreen? 'md-size-100' : 'md-size-35' "
                 v-if="financialData">
                <md-card class="chart-card">
                    <md-card-header>
                        <md-card-header-text>
                            {{ $tc('phrases.revenueLine') }}
                        </md-card-header-text>
                        <md-menu class="md-medium-hide" md-size="big" md-direction="bottom-end">
                            <md-button class="md-icon-button" md-menu-trigger
                                       @click="maximize('lineChartFullScreen')">
                                <md-icon>fullscreen</md-icon>
                            </md-button>

                        </md-menu>
                    </md-card-header>
                    <md-card-content>
                        <GChart
                            type="LineChart"
                            :data="financialDataChart('line', false)"
                            :options="chartOptions"
                            :resizeDebounce="500"
                            ref="gChart"
                            :events="chartEvents"
                        />
                    </md-card-content>
                </md-card>
            </div>
            <!-- Column Chart -->
            <div class="md-layout-item md-size-35 md-medium-size-100"
                 v-if="financialData"
                 :class="barChartFullScreen? 'md-size-100' : 'md-size-35' ">
                <md-card class="chart-card">
                    <md-card-header>
                        <md-card-header-text>
                            {{ $tc('phrases.revenueColumns') }}
                        </md-card-header-text>
                        <md-menu class="md-medium-hide" md-size="big" md-direction="bottom-end">
                            <md-button class="md-icon-button" md-menu-trigger
                                       @click="maximize('barChartFullScreen')">
                                <md-icon>fullscreen</md-icon>
                            </md-button>

                        </md-menu>
                    </md-card-header>
                    <md-card-content>
                        <GChart
                            type="ColumnChart"
                            :data="financialDataChart('column')"
                            :options="chartOptions"
                            :resizeDebounce="500"
                            ref="gChart"
                            :events="chartEvents"
                        />
                    </md-card-content>
                </md-card>
            </div>

            <div class="md-layout-item md-size-30 md-medium-size-100"
                 v-if="financialData"
                 :class="donutChartFullScreen? 'md-size-100' : 'md-size-30' ">
                <md-card class="chart-card">
                    <md-card-header>
                        <md-card-header-text>
                            {{ $tc('phrases.revenuePercentiles') }}
                        </md-card-header-text>
                        <md-menu class="md-medium-hide" md-size="big" md-direction="bottom-end">
                            <md-button class="md-icon-button" md-menu-trigger
                                       @click="maximize('donutChartFullScreen')">
                                <md-icon>fullscreen</md-icon>
                            </md-button>

                        </md-menu>
                    </md-card-header>
                    <md-card-content>
                        <GChart
                            type="PieChart"
                            :data="financialDataChart('column')"
                            :options="chartOptions"
                            :resizeDebounce="500"
                            ref="gChart"
                            :events="chartEvents"
                        />
                    </md-card-content>
                </md-card>

            </div>
        </div>


    </widget>


</template>

<script>
import Widget from '../../shared/widget'
import moment from 'moment'
import { ClusterService } from '../../services/ClusterService'

export default {
    name: 'FinancialOverview',
    components: { Widget },
    props: {
        clusterId: {
            type: String,
            default: '1'
        },
        financialData: {
            required: true
        }
    },
    data () {
        return {
            clusterService: new ClusterService(),
            lineChartFullScreen: false,
            barChartFullScreen: false,
            donutChartFullScreen: false,
            loading: false,
            periodText: '2019.01.01 - Today',
            period: {
                from: null,
                to: null,
            },
            setPeriod: false,
            clicks: 0, //to detect a double click on a chart
            chartOptions: {
                chart: {
                    title: 'Customer Payment Flow',
                    subtitle: 'Sales, Expenses, and Profit: 2014-2017',
                },
            },
            chartEvents: {
                select: () => {
                },
                click: () => {
                    this.clicks++
                    let parent = this
                    setTimeout(function () {
                        if (parent.clicks >= 2) {
                            parent.chartType = parent.toggleChartType()

                        }
                        parent.clicks = 0
                    }, 250)
                },
            },
            disabled: {
                customPredictor:
                    function (date) {
                        let today = new Date()
                        let minDate = new Date('2018-01-01')
                        // disables the date if it is a multiple of 5
                        if (date > today || date < minDate) {
                            return true
                        }
                    }
            },
        }
    },
    mounted () {
        this.clusterService.financialData = this.financialData
    },
    methods: {
        showPeriod () {
            this.setPeriod = !this.setPeriod
        },
        async getClusterFinancialData () {
            try {
                this.loading = true
                let from = this.period.from !== null ? moment(this.period.from).format('YYYY-MM-DD') : null
                let to = this.period.to !== null ? moment(this.period.to).format('YYYY-MM-DD') : null

                this.financialData = await this.clusterService.getClusterCitiesRevenue(this.clusterId, 'monthly',
                    from, to)

                this.loading = false

                if (from !== null) {

                    this.periodText = from + ' - ' + to
                }

            } catch (e) {
                this.alertNotify('error', e.message)

            }
            this.setPeriod = false
            this.loading = false
        },
        financialDataChart (type, summary = false) {
            let data = []
            if (type === 'column') {
                return this.clusterService.columnChartData(summary, type = 'miniGrid')
            } else if (type === 'line') {
                return this.clusterService.lineChartData(summary)
            }
            return data
        },
        dateSelectedFrom (date) {
            this.setDate(date, 'from')
        },
        dateSelectedTo (date) {
            this.setDate(date, 'to')
        },
        setDate (dateData, target) {
            let date = moment(dateData)
            if (target === 'from') {
                this.period.from = date.format('YYYY-MM-DD')
            } else {
                this.period.to = date.format('YYYY-MM-DD')
            }
        },
        maximize (data) {
            //eval('this.data = !this.data')
            if (data === 'lineChartFullScreen') {
                this.lineChartFullScreen = !this.lineChartFullScreen
            } else if (data === 'barChartFullScreen') {
                this.barChartFullScreen = !this.barChartFullScreen
            } else if (data === 'donutChartFullScreen') {
                this.donutChartFullScreen = !this.donutChartFullScreen
            }
            window.dispatchEvent(new Event('resize'))
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

<style lang="scss">
.datepicker-right .vdp-datepicker__calendar {
    right: 0;
}

.period-selector {
    position: absolute;
    top: 0;
    right: 0;
    z-index: 9999;
    padding: 15px;
    background-color: white;
    border: 1px solid #ccc;
}

.md-datepicker-dialog {
    z-index: 1000 !important;
}

.chart-card {
    margin-bottom: 1vh;
}


</style>
