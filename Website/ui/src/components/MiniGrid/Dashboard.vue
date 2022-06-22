<template>
    <div>
        <section id="widget-grid">
            <div v-if="expanded === false" class="md-size-100" style="margin-bottom: 1.3vh;">

                <md-toolbar class="md-primary">
                    <div class="md-layout-item md-size-60">
                        <md-tabs class="md-primary" md-alignment="left" :md-active-tab="'tab-'+tab">
                            <md-tab id="tab-weekly" :md-label="$tc('words.week',2)" @click="tab = 'weekly'"></md-tab>
                            <md-tab id="tab-monthly" :md-label="$tc('words.month',2)" @click="tab = 'monthly'"></md-tab>
                            <md-tab id="tab-annually" :md-label="$tc('words.annually')" @click="tab = 'annually'"></md-tab>

                        </md-tabs>
                    </div>

                    <div class="md-toolbar-section-end">
                        <md-button class="md-raised  md-dense" @click="getBatchData"
                                   :disabled="Object.keys(highlighted.base).length===0">{{ $tc('words.apply') }}
                        </md-button>
                        <md-button class="md-raised md-accent md-dense" @click="closeDatePicker">{{ $tc('words.close') }}</md-button>
                    </div>

                </md-toolbar>


                <div class="md-layout md-size-90">
                    <div class="text-center md-layout md-gutter" v-if="tab==='weekly'" :key="tab">
                        <div class="md-layout-item md-size-50" style="margin-bottom: 1vh;">
                            <h4>{{ $tc('words.compared') }}</h4>
                            <datepicker :inline="true"
                                        v-model="highlighted.compared.from"
                                        :monday-first="true"
                                        :disabledDates="disabled"
                            ></datepicker>
                        </div>
                        <div class="md-layout-item md-size-50" style="margin-bottom: 1vh;">
                            <h4>{{ $tc('words.base') }}</h4>
                            <datepicker :inline="true"
                                        :monday-first="true"
                                        :disabledDates="disabled"
                                        v-model="highlighted.tmpBase.from"
                                        >

                            </datepicker>

                        </div>

                    </div>

                    <div class="text-center md-layout md-gutter" v-if="tab==='monthly'" :key="tab">
                        <div class="md-layout-item md-size-50" style="margin-bottom: 1vh;">
                            <h4>{{ $tc('words.compared') }}</h4>
                            <datepicker :inline="true"
                                        :minimum-view="'month'"
                                        :maximum-view="'year'"
                                        v-model="highlighted.tmpCompared.from"
                                        :disabledDates="disabled"/>
                        </div>
                        <div class="md-layout-item md-size-50" style="margin-bottom: 1vh;">
                            <h4>{{ $tc('words.base') }}</h4>
                            <datepicker :inline="true"
                                        :minimum-view="'month'"
                                        :maximum-view="'year'"
                                        v-model="highlighted.tmpBase.from"
                                        :disabledDates="disabled"/>
                        </div>


                    </div>


                    <div class="text-center md-layout md-gutter" v-if="tab==='annually'" :key="tab">
                        <div class="md-layout-item md-size-50" style="margin-bottom: 1vh;">
                            <h4>{{ $tc('words.compared') }}</h4>
                            <datepicker :inline="true"
                                        :minimum-view="'year'"
                                        :maximum-view="'year'"
                                        v-model="highlighted.compared.from"
                                        :disabledDates="disabled"/>
                        </div>
                        <div class="md-layout-item md-size-50" style="margin-bottom: 1vh;">
                            <h4>{{ $tc('words.base') }}</h4>
                            <datepicker :inline="true"
                                        :minimum-view="'year'"
                                        :maximum-view="'year'"
                                        v-model="highlighted.base.from"
                                        :disabledDates="disabled"/>
                        </div>

                    </div>
                </div>
                <md-divider style="height: 1vh; background-color: #90CAF9 !important;"></md-divider>

            </div>
            <!-- modal-->
            <div class="md-layout md-gutter">
                <div class="md-layout-item md-medium-size-100  md-xsmall-size-100 md-size-100">
                    <md-toolbar style="margin-bottom: 3rem;">

                        <md-menu
                            md-direction="bottom-end"
                            md-size="big"
                            :md-offset-x="127" :md-offset-y="-36">
                            <md-button md-menu-trigger>
                                <md-icon>keyboard_arrow_down</md-icon>
                                {{ $tc('words.miniGrid') }}: {{ miniGridData.name }}
                            </md-button>
                            <md-menu-content>
                                <md-menu-item v-for="(miniGrid ,key)  in miniGrids" :key="key"
                                              @click="setMiniGrid(miniGrid.id)">
                                    <span>{{miniGrid.name}}</span>
                                    <md-icon v-if="miniGrid.data_stream === 1">check</md-icon>
                                </md-menu-item>

                            </md-menu-content>
                        </md-menu>

                        <md-switch v-model="enableDataStream" @change="onDataStreamChange($event)" :disabled="switching"
                                   class="data-stream-switch">
                            <span v-if="!enableDataStream">{{ $tc('words.activate') }}  {{ $tc('phrases.dataLogger',0) }} </span>
                            <span v-else> {{ $tc('words.deactivate') }}  {{ $tc('phrases.dataLogger',0) }} </span>
                        </md-switch>
                        <div class="md-toolbar-section-end">

                        <span style="float: left">
                    {{ $tc('words.period') }} : {{this.startDate}} - {{this.endDate}} {{checkToday()}}
                </span>
                            <md-button class="md-raised" @click="openDatePicker" v-show="!selectorOpened">
                                <md-icon>calendar_today</md-icon>
                                {{ $tc('phrases.selectPeriod') }}
                            </md-button>

                        </div>
                    </md-toolbar>
                </div>
                <div class="md-layout-item md-size-100 ">
                    <box-group
                        ref="box"
                        :mini-grid-id="miniGridId"
                    ></box-group>
                </div>


                <div style="margin-top:1rem">&nbsp;</div>


                <div class="md-layout-item md-size-100" v-if="enableDataStream ">
                    <energy-chart-box :mini-grid-id="miniGridId"/>
                </div>

                <div class="md-layout-item md-layout md-gutter md-size-100">
                    <div class="md-layout-item md-medium-size-100 md-size-33" style="min-height: 500px">
                        <revenue-per-customer-type
                            ref="donut"
                            :batch-revenues="batchRevenues"
                        ></revenue-per-customer-type>

                    </div>
                    <div class="md-layout-item md-medium-size-100 md-size-66" style="min-height: 500px">
                        <revenue-target-per-customer-type
                            ref="targetChart"
                        ></revenue-target-per-customer-type>

                    </div>
                </div>

                <div class="md-layout-item md-size-100">
                    <mini-grid-map :mini-grid-id="miniGridId"/>
                </div>
                <div class="md-layout-item md-size-100">
                    <target-list
                        ref="target"
                        :target-id="miniGridId"
                        target-type="mini-grid"
                        :base="highlighted.base"
                        :compared="highlighted.compared"
                    />
                </div>
                <div class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100">
                    <revenue-trends
                        ref="revenue"
                        :mini-grid-id="miniGridId"
                        :chart-options="chartOptions"
                    ></revenue-trends>
                </div>

                <div class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100">
                    <tickets-overview
                        ref="tickets"
                        :chart-options="chartOptions"
                        :mini-grid-id="miniGridId"
                    ></tickets-overview>
                </div>
            </div>

            <transition name="modal" v-if="showModal">
                <div class="modal-mask">
                    <div class="modal-wrapper">
                        <div class="modal-container">
                            <md-card class="md-size-100">
                                <md-card-header>
                                    <h3>{{ $tc('words.edit') }} {{miniGridData.name}}</h3>
                                </md-card-header>
                                <md-card-content>
                                    <md-field>
                                        <label for="mini-grid-name">{{ $tc('words.name') }}</label>
                                        <md-input type="text" id="mini-grid-name" class="form-control"
                                                  :value="miniGridData.name"></md-input>
                                    </md-field>

                                    <md-field>
                                        <label for="mini-grid-location">{{ $tc('words.location') }}</label>
                                        <md-input type="text" id="mini-grid-location"
                                                  class="form-control"
                                                  :value="miniGridData.location!== undefined ? miniGridData.location.points: ''"
                                                  placeholder="Latitude, Longitude"></md-input>


                                    </md-field>
                                </md-card-content>
                                <md-card-actions>
                                    <md-button class="md-raised md-accent" @click="showModal = false">
                                        <md-icon>cancel</md-icon>
                                        {{ $tc('words.close') }}
                                    </md-button>

                                    <md-button @click="updateMiniGrid" class="md-raised md-primary">
                                        {{ $tc('words.update') }}
                                    </md-button>
                                </md-card-actions>
                            </md-card>


                        </div>
                    </div>
                </div>
            </transition>
            <!-- purchasing modal-->
            <md-dialog :md-active.sync="ModalVisibility"
            >

                <md-dialog-content>
                    <stepper :watchingMiniGrids="watchingMiniGrids" :purchasingType="'logger'" v-if="ModalVisibility">

                    </stepper>
                </md-dialog-content>
            </md-dialog>
            <!-- purchasing modal-->
        </section>

    </div>

</template>

<script>
import format from 'date-fns/format'
import moment from 'moment'
import { currency } from '@/mixins/currency'
import Datepicker from 'vuejs-datepicker'
import { BatchRevenue } from '@/classes/revenue/batch'
import TargetList from './TargetList'
import EnergyChartBox from './EnergyChartBox'
import { MiniGridService } from '@/services/MiniGridService'
import Stepper from '../../shared/stepper'
import { EventBus } from '@/shared/eventbus'
import MiniGridMap from './MiniGridMap'
import { RevenueService } from '@/services/RevenueService'
import BoxGroup from './BoxGroup'
import TicketsOverview from './TicketsOverview'
import RevenueTrends from './RevenueTrends'
import RevenuePerCustomerType from './RevenuePerCustomerType'
import RevenueTargetPerCustomerType from './RevenueTargetPerCustomerType'
import { BatchRevenueService } from '@/services/BatchRevenueService'

export default {
    name: 'Dashboard',
    components: {
        RevenueTargetPerCustomerType,
        EnergyChartBox,
        MiniGridMap,
        TargetList,
        Datepicker,
        RevenueTrends,
        Stepper,
        BoxGroup,
        TicketsOverview,
        RevenuePerCustomerType

    },
    mixins: [currency],
    created () {
        this.miniGridId = this.$route.params.id
        this.redirectionUrl += '/' + this.miniGridId
        this.getMiniGridData(this.miniGridId)

    },
    mounted () {
        //set initial dates for periods
        this.getBatchData()
        this.getMiniGridList()
        EventBus.$on('closeModal', this.closeModal)

    },
    watch: {
        $route: function(){
            this.$router.go()
        },
        compareData: function () {
            if (this.compareData.length === 0) {
                return
            }
            this.compareTotal = 0
            for (let i = 0; i < this.actualData.length; i++) {
                this.compareTotal += parseInt(this.compareData[i].amount)
                let old = this.chartTmpData[i + 1]
                old.push(parseInt(this.compareData[i].amount))
                let parsedIndex = old[0].split(' - ')
                if (parsedIndex.length > 1) {
                    old[0] = parsedIndex[0] + ' - ' + this.compareData[i].date
                } else {
                    old[0] += ' - ' + this.compareData[i].date
                }
                this.chartTmpData[i + 1] = old
            }
            this.refreshChart()
        },
        actualData: function () {
            if (this.actualData.length === 0) {
                return
            }
            this.actualTotal = 0
            for (let i = 0; i < this.actualData.length; i++) {
                this.actualTotal += parseInt(this.actualData[i].amount)
                this.chartTmpData.push(
                    [this.actualData[i].date, parseInt(this.actualData[i].amount),],
                )
            }
        },
    },
    computed: {
        compareAnalysisAvailable () {
            return this.comparedRevenues.revenueList !== null
        },
        //the summary of total revenues of both periods
        totalRevenues () {
            if (this.revenues.length === 0) {
                return 0
            }
            let sum = {
                revenue: 0,
                compareRevenue: 0,
                connections: 0,
                compareNewConnections: 0,
                totalConnections: 0,
                revenuePerConnection: 0,
                compareRevenuePerConnection: 0
            }
            for (let i in this.revenues) {
                let revenues = this.revenues[i]
                sum['revenue'] += revenues.revenue
                sum['compareRevenue'] += revenues.compareRevenue
                sum['connections'] += revenues.newConnections
                sum['compareNewConnections'] += revenues.compareNewConnections
                sum['totalConnections'] += revenues.totalConnections
                sum['revenuePerConnection'] += revenues.revenuePerConnection
                sum['compareRevenuePerConnection'] += revenues.compareRevenuePerConnection
            }
            return sum
        },

    },
    data () {
        return {
            subscriber: {
                revenue_trends: 'mini-grid-revenue-trends'
            },
            miniGridService: new MiniGridService(),
            revenueService: new RevenueService(),
            batchRevenueService: new BatchRevenueService(),
            revenueTrends: null,
            ticketsData: null,
            enableDataStream: false,
            isLoggerActive: false,
            ModalVisibility: false,
            switching: false,
            watchingMiniGrids: [],
            activeStep: 'firstStep',
            firstStep: false,
            secondStep: false,
            thirdStep: false,
            purchaseCode: '',
            showModal: false,
            miniGridData: {},
            miniGridId: null,
            selectedMiniGrid: this.$route.params.id,
            miniGrids: [],
            activeDateTab: 'tab-monthly',
            startDate: null,
            endDate: null,
            showSelector: false,
            chartEvents:
                {
                    select: () => {
                        const table = this.$refs.gChart.chartObject
                        const selection = table.getSelection()
                        const onSelectionMessage = selection.length !== 0 ? 'row was selected' : 'row was diselected'
                        console.log(onSelectionMessage)
                    }
                },
            chartOptions: {
                isStacked: true,
                chart: {
                    legend: {
                        position: 'top'
                    }
                    ,
                },
                hAxis: {
                    textPosition: 'out',
                    slantedText:
                        true
                },
                vAxis: {
                    //scaleType: 'mirrorLog',
                },
                height: '600',
            },
            chartOptionsSmall: {
                chart: {
                    legend: {
                        position: 'top'
                    },
                },
                hAxis: {
                    textPosition: 'out',
                    slantedText: true
                },
                vAxis: {
                    //scaleType: 'mirrorLog',
                },
                colors: ['#739e73', '#448aff', '#78002e', '#dce775',],
                height: 220,
            },
            periodMap: [],
            //bach revenue controller instance
            batchRevenues: new BatchRevenue(),
            comparedRevenues: new BatchRevenue(),
            //hold the target(s) data
            targets: [],
            //is the counter which is used to determine which data should displayed
            periodMapIterator: 0,
            //selected tartget
            selectedTargetData: [],
            //holds the ticket data for selected period
            currentTickets: {},
            disabled: {
                days: [0, 2, 3, 4, 5, 6], // Disable all days except monday
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
            highlighted: {
                base: {},
                compared: {},
                tmpBase: {},
                tmpCompared: {},
            },
            tab: 'monthly',
            expanded: true, // is the determinator whether the period picker should be displayed or not
            revenues: [],
            tmpRevenues: [],
            init: true,
            dateFormat: 'DD MMM YYYY',
            labels: ['Base', 'Comparision'],
            chartData: [],
            chartTmpData: [],
            selectorOpened: false
        }
    },
    methods: {
        checkToday(){
            if(moment().format('YYYY-MM-DD') === this.endDate){
                return '(Today)'
            }
        },
        setMiniGrid(miniGridId){
            this.$router.replace('/dashboards/mini-grid/' + miniGridId)
        },
        async getMiniGridList() {
            try {
                this.miniGrids = await this.miniGridService.getMiniGrids()
            } catch (e) {
                this.alertNotify('error', 'Error: failed to get MiniGrid list')
            }
        },
        closeModal () {
            this.ModalVisibility = false
        },
        closeDatePicker () {
            this.selectorOpened = false
            this.expanded = true
        },
        openDatePicker () {
            this.selectorOpened = true
            this.expanded = false
        },
        editMiniGrid () {
            this.showModal = true
        },
        async getMiniGridData (miniGridId) {
            try {
                this.miniGridData = await this.miniGridService.getMiniGridData(miniGridId)
                this.enableDataStream = this.miniGridData.data_stream === 1 ? true : false
                this.isLoggerActive = this.enableDataStream
            } catch (e) {
                this.alertNotify('error', 'Error: failed to get MiniGrid data')
            }
        },
        async onDataStreamChange (value) {
            try {
                this.switching = true
                let data_stream = this.enableDataStream === true ? 1 : 0
                await this.miniGridService.setMiniGridDataStream(this.miniGridId, data_stream)
                let message = value === true ? this.$tc('phrases.dataLogger', 1) : this.$tc('phrases.dataLogger', 2)
                this.alertNotify('success', message)
                this.isLoggerActive = value
                this.enableDataStream = value
                this.switching = false
            } catch (e) {
                this.switching = false
                this.alertNotify('warn', e.message)
                this.isLoggerActive = !value
                this.enableDataStream = !value
                try {
                    this.watchingMiniGrids = await this.miniGridService.getMiniGridDataStreams(1)
                    this.ModalVisibility = true
                } catch (e) {
                    this.alertNotify('error', e.message)
                }
            }
        },

        totalConnectionsByTarget () {
            let totalConnections = 0
            if (this.periodMap.length === 0) return totalConnections
            this.periodMap[0].targets.map(item => {
                totalConnections += item.new_connections
                return item
            })
            return totalConnections
        },
        calculateRevenueTargetPercentage (revenue, targetRevenue) {
            if (revenue === 0 || targetRevenue === 0) return 0
            return Math.round(
                parseInt(revenue) * 100
                / parseInt(targetRevenue)
                * 100
            ) / 100
        },
        baseTargetData (connectionType, type) {
            if (this.periodMap.length === 0) return 'Target not available'
            let matchTarget = this.periodMap[0].targets.filter(target => {
                return target.connection === connectionType
            })
            if (matchTarget.length === 0) {
                return 'no data for ' + connectionType
            }
            if (type === 'connections') {
                return matchTarget[0].new_connections
            } else if (type === 'revenue') {
                return matchTarget[0].revenue
            }
        },
        //returns a readable string based on [seconds]
        calcutateDuration (seconds) {
            if (seconds === null) return '0'
            seconds = parseInt(seconds)
            return Math.floor(moment.duration(seconds, 'seconds').asHours()) + ':' + moment.duration(seconds, 'seconds').minutes() + ':' + moment.duration(seconds, 'seconds').seconds()
        },
        //re-formats the date
        formatPeriodText (date) {
            return moment(date, 'Y-W').format('YYYY MMM Do')
        },

        revenueData (from, to, batchRevenues) {
            return batchRevenues.revenueForPeriod(
                this.miniGridId,
                'mini-grid',
                from,
                to,
            ).then((data) => {
                return data
            })
        },
        getBatchData(){
            this.selectorOpened = false
            this.expanded = true
            let baseDate = moment(this.highlighted.tmpBase.from)
            let comparedDate = moment(this.highlighted.tmpCompared.from)
            if (this.tab === 'weekly') {
                let baseNextSunday = moment(this.highlighted.tmpBase.from, 'YYYY-MM-DD').add(6, 'days')
                let comparedNextSunday = moment(this.highlighted.tmpCompared.from, 'YYYY-MM-DD').add(6, 'days')
                this.highlighted.tmpBase = {
                    from: moment(baseDate).format('YYYY-MM-DD'),
                    to: moment(baseNextSunday).format('YYYY-MM-DD'),
                    includeDisabled: true // Highlight disabled dates
                }
                this.highlighted.tmpCompared = {
                    from: moment(comparedDate).format('YYYY-MM-DD'),
                    to: moment(comparedNextSunday).format('YYYY-MM-DD'),
                    includeDisabled: true // Highlight disabled dates
                }

            }

            if (this.tab === 'monthly') {
                this.highlighted.tmpBase = {
                    from: baseDate.format('YYYY-MM-01'),
                    to: baseDate.add(1, 'months').date(0).format('YYYY-MM-DD'),
                    includeDisabled: true // Highlight disabled dates
                }
                this.highlighted.tmpCompared = {
                    to: comparedDate.add(1, 'months').date(0).format('YYYY-MM-DD'),
                    from: comparedDate.format('YYYY-MM-01'),
                    includeDisabled: true // Highlight disabled dates
                }
            }
            if (this.tab === 'annually') {
                this.highlighted.tmpBase = {
                    from: baseDate.format('YYYY-01-01'),
                    to: baseDate.format('YYYY-12-31'),
                    includeDisabled: true // Highlight disabled dates
                }
                this.highlighted.tmpCompared = {
                    from: comparedDate.format('YYYY-01-01'),
                    to: comparedDate.format('YYYY-12-31'),
                    includeDisabled: true // Highlight disabled dates
                }

            }

            this.highlighted.compared = this.highlighted.tmpCompared
            this.highlighted.base = this.highlighted.tmpBase
            if (this.highlighted.base.from < this.highlighted.compared.from) {
                this.startDate = this.highlighted.base.from
                if(moment().format('YYYY-MM-DD') < this.highlighted.compared.to){
                    this.endDate = moment().format('YYYY-MM-DD')
                }else{
                    this.endDate = this.highlighted.compared.to
                }

            } else{
                this.startDate = this.highlighted.compared.from
                if(moment().format('YYYY-MM-DD') < this.highlighted.base.to){
                    this.endDate = moment().format('YYYY-MM-DD')
                }else{
                    this.endDate = this.highlighted.base.to
                }

            }
            this.getBatchRevenues()
            this.$refs.box.getSoldEnergy(this.startDate,this.endDate)
            this.$refs.box.getTransactionsOverview(this.startDate,this.endDate)
            this.$refs.revenue.getRevenueTrends(this.startDate,this.endDate,this.tab)
            this.$refs.tickets.getTicketsData()

        },
        async getBatchRevenues(){
            try {
                this.batchRevenues = await this.batchRevenueService.getRevenueForPeriod(this.miniGridId,'mini-grid',this.startDate,this.endDate)
                this.$refs.donut.initDonutData(this.batchRevenues)
                this.$refs.targetChart.getColumnChartData(this.batchRevenues)
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        formatDates (dateOne, dateTwo) {
            let formattedDates = ''
            if (dateOne) {
                formattedDates = format(dateOne, this.dateFormat)
            }
            if (dateTwo) {
                formattedDates += ' - ' + format(dateTwo, this.dateFormat)
            }
            return formattedDates !== '' ? formattedDates : 'Select Dates'
        },
        refreshChart () {
            this.chartData = this.chartTmpData
        },
        calculateRevenuePercent (current, compared) {
            if (current + compared === 0) return -1
            return Math.round(current * 100 / compared)
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

<style>
.asd__inner-wrapper {
    margin-left: 0 !important;
}

.date-button {
    overflow: hidden;
    max-width: 100%;
}

.base-color, .green {
    color: #739e73
}

.compare-color {
    color: #448aff;
}

.red {
    color: #ba0f0d;
}

.base-color-bg {
    background-color: #739e73 !important;
    color: whitesmoke !important;
}

.compare-color-bg {
    background-color: #448aff !important;
    color: whitesmoke !important;
}

.progress {
    margin-bottom: 6px !important;
}

.close-period {
    transition: all 600ms;
    -webkit-transition: all 0.5s; /* Safari */
    cursor: pointer;
    background-color: #1b1e21;
    border-top-left-radius: 15px;
    border-bottom-left-radius: 15px;
    color: whitesmoke;
    padding: 10px;
    border: 1px solid;
    font-size: 1.2rem;
    position: absolute;
    left: -12%;
    top: 0;
}

.close-period:hover {
    left: 0;
    padding-left: 48px;
    margin-left: -50px;
}

.close-period > button {
    font-size: 2rem;
    background-color: #7f9919;
    color: whitesmoke;
}

.open-period {
    cursor: pointer;
    background-color: #1b1e21;
    border-top-left-radius: 15px;
    border-bottom-left-radius: 15px;
    color: whitesmoke;
    padding: 10px;
    border: 1px solid;
    font-size: 1.2rem;
    position: absolute;
    top: 1rem;
    -webkit-transition: padding-right 0.5s, color 0.5s, background-color 0.5s; /* Safari */
    transition: padding-right 0.5s, color 0.5s, background-color 0.5s;
    right: 0;
}

.open-period:hover {
    padding-right: 250px;
    background-color: #c7cfdc;
    border: #cccccc;
    color: #1b1e21;
}

div {
    transition: all 600ms;
    -webkit-transition: all 0.5s; /* Safari */
}

.period-indicator {
    opacity: 0.8;
    cursor: pointer;
}

.pull-left.navigation-padding {
    padding-left: 15px;
}

.pull-right.navigation-padding {
    padding-right: 15px;
}

.period-navigation {
    background-color: #448aff;
    padding: 5px;
    color: white;
    border: 1px;
    font-weight: 600;
    letter-spacing: 4.2px;
    border-radius: 11px;
    font-size: 1.5rem;
    margin-bottom: 2rem;
}

.period-navigation > .arrows {
    position: absolute;
    top: 1.5rem;
}

.arrows.right {
    right: 2rem;
}

.arrows.left {
    left: 2rem;
}

    .progress-title {
        font-size: 16px;
        font-weight: 700;
        color: #333;
        margin: 0 0 20px;
    }

.progress {
    height: 10px;
    background: #333;
    border-radius: 0;
    box-shadow: none;
    margin-bottom: 30px;
    overflow: visible;
}

.progress .progress-bar {
    position: relative;
    -webkit-animation: animate-positive 2s;
    animation: animate-positive 2s;
}

.progress .progress-bar:after {
    content: "";
    display: inline-block;
    width: 9px;
    background: #fff;
    position: absolute;
    top: -10px;
    bottom: -10px;
    right: -1px;
    z-index: 1;
    transform: rotate(35deg);
}

.progress .progress-value {
    display: block;
    font-size: 16px;
    font-weight: 600;
    color: #333;
    position: absolute;
    top: -30px;
    right: -25px;
}

@-webkit-keyframes animate-positive {
    0% {
        width: 0;
    }
}

@keyframes animate-positive {
    0% {
        width: 0;
    }
}

.tooltip {
    display: block !important;
    z-index: 10000;
}

.tooltip .tooltip-inner {
    background: black;
    color: white;
    border-radius: 16px;
    padding: 5px 10px 4px;
}

.tooltip .tooltip-arrow {
    width: 0;
    height: 0;
    border-style: solid;
    position: absolute;
    margin: 5px;
    border-color: black;
    z-index: 1;
}

.tooltip[x-placement^="top"] {
    margin-bottom: 5px;
}

.tooltip[x-placement^="top"] .tooltip-arrow {
    border-width: 5px 5px 0 5px;
    border-left-color: transparent !important;
    border-right-color: transparent !important;
    border-bottom-color: transparent !important;
    bottom: -5px;
    left: calc(50% - 5px);
    margin-top: 0;
    margin-bottom: 0;
}

.tooltip[x-placement^="bottom"] {
    margin-top: 5px;
}

.tooltip[x-placement^="bottom"] .tooltip-arrow {
    border-width: 0 5px 5px 5px;
    border-left-color: transparent !important;
    border-right-color: transparent !important;
    border-top-color: transparent !important;
    top: -5px;
    left: calc(50% - 5px);
    margin-top: 0;
    margin-bottom: 0;
}

.tooltip[x-placement^="right"] {
    margin-left: 5px;
}

.tooltip[x-placement^="right"] .tooltip-arrow {
    border-width: 5px 5px 5px 0;
    border-left-color: transparent !important;
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
    left: -5px;
    top: calc(50% - 5px);
    margin-left: 0;
    margin-right: 0;
}

.tooltip[x-placement^="left"] {
    margin-right: 5px;
}

.tooltip[x-placement^="left"] .tooltip-arrow {
    border-width: 5px 0 5px 5px;
    border-top-color: transparent !important;
    border-right-color: transparent !important;
    border-bottom-color: transparent !important;
    right: -5px;
    top: calc(50% - 5px);
    margin-left: 0;
    margin-right: 0;
}

.tooltip.popover .popover-inner {
    background: #f9f9f9;
    color: black;
    padding: 24px;
    border-radius: 5px;
    box-shadow: 0 5px 30px rgba(black, .1);
}

.tooltip.popover .popover-arrow {
    border-color: #f9f9f9;
}

.tooltip[aria-hidden='true'] {
    visibility: hidden;
    opacity: 0;
    transition: opacity .15s, visibility .15s;
}

.tooltip[aria-hidden='false'] {
    visibility: visible;
    opacity: 1;
    transition: opacity .15s;
}

.vdp-datepicker__calendar {
    width: 100% !important;
}

.dot {
    height: 1.5rem;
    width: 1.5rem;
    border-radius: 50%;
    display: inline-block;
}

.dot.revenue {
    background-color: #739e73;
}

.dot.new-connection {
    background-color: #c79121;
}

.modal-mask {
    position: fixed;
    z-index: 1001;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, .5);
    display: table;
    transition: opacity .3s ease;
}

.modal-wrapper {
    display: table-cell;
    vertical-align: middle;
}

.modal-container {
    margin: 0px auto;
    padding: 20px 30px;
    background-color: #fff;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .33);
    transition: all .3s ease;
    font-family: Helvetica, Arial, sans-serif;
    max-height: 85%;
    overflow-y: scroll;
}

@media only screen and (max-width: 1024px) {
    .modal-container {
        width: 99% !important;
    }
}

@media only screen and (min-width: 1024px) {
    .modal-container {
        width: 55% !important;
    }
}

.modal-header h3 {
    margin-top: 0;
    color: #42b983;
}

.modal-body {
    margin: 20px 0;
}

.modal-default-button {
    float: right;
}

/*
 * The following styles are auto-applied to elements with
 * transition="modal" when their visibility is toggled
 * by Vue.js.
 *
 * You can easily play with the modal transition by editing
 * these styles.
 */
.modal-enter {
    opacity: 0;
}

.modal-leave-active {
    opacity: 0;
}

.modal-enter .modal-container,
.modal-leave-active .modal-container {
    -webkit-transform: scale(1.1);
    transform: scale(1.1);
}

.exclamation {
    margin: auto;
    align-items: center;
    display: inline-grid;
    text-align: center;
}

.watched-miniGrid-List {
    font-size: 11px;
    width: 15%;
    margin: auto;
    font-weight: bold;
}

.exclamation-div {
    margin-top: 2% !important;
}

.data-stream-switch {
    margin-left: 3rem !important;
}

.vdp-datepicker__calendar .cell.selected {
    background: #90CAF9 !important;
}
</style>
