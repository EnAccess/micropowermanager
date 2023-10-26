<template>
    <div>
        <section id="widget-grid">

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
                                    <span>{{ miniGrid.name }}</span>
                                    <md-icon v-if="miniGrid.data_stream === 1">check</md-icon>
                                </md-menu-item>

                            </md-menu-content>
                        </md-menu>

                        <md-switch v-model="enableDataStream" @change="onDataStreamChange($event)" :disabled="switching"
                                   class="data-stream-switch">
                            <span v-if="!enableDataStream">{{ $tc('words.activate') }}  {{
                                    $tc('phrases.dataLogger', 0)
                                }} </span>
                            <span v-else> {{ $tc('words.deactivate') }}  {{ $tc('phrases.dataLogger', 0) }} </span>
                        </md-switch>

                        <div class="md-toolbar-section-end">

                            <span style="float: left">Selected Period: {{ periodText }} </span>
                            <md-button class="md-icon-button md-dense md-raised" @click="togglePeriod">
                                <md-icon>calendar_today</md-icon>
                            </md-button>
                            <div v-if="setPeriod" class="period-selector">
                                <p>{{ $tc('phrases.selectPeriod') }}</p>
                                <div class="md-layout md-gutter">
                                    <div class="md-layout-item md-size-100">
                                        <md-datepicker v-model="period.from" md-immediately v-validate="'required'">
                                            <label>{{ $tc('phrases.fromDate') }}</label>
                                        </md-datepicker>
                                        <span class="md-error">{{ errors.first($tc('phrases.fromDate')) }}</span>
                                    </div>
                                    <div class="md-layout-item md-size-100">
                                        <md-datepicker v-model="period.to" md-immediately v-validate="'required'">
                                            <label>{{ $tc('phrases.toDate') }}</label>
                                        </md-datepicker>
                                        <span class="md-error">{{ errors.first($tc('phrases.toDate')) }}</span>
                                    </div>
                                </div>
                                <div style="margin-top: 5px;">
                                    <md-progress-bar md-mode="indeterminate" v-if="loading"/>
                                    <button style="width:100%;" v-if="!loading" class="btn btn-primary"
                                            @click="onPeriodChange">
                                        {{ $tc('words.send') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </md-toolbar>
                </div>
                <div class="md-layout-item md-size-100 ">
                    <box-group
                        ref="box"
                        :mini-grid-id="miniGridId"
                        :miniGridData="miniGridData"
                    ></box-group>
                </div>
                <div class="md-layout-item md-size-100" v-if="enableDataStream" style="margin-top: 3rem">
                    <energy-chart-box :mini-grid-id="miniGridId"/>
                </div>

                <div class="md-layout-item md-layout md-gutter md-size-100 " style="z-index: -1">
                    <div class="md-layout-item md-medium-size-100 md-size-33" style="min-height: 500px">
                        <revenue-per-customer-type :donutData="donutData" :donutChartOptions="donutChartOptions"/>
                    </div>
                    <div class="md-layout-item md-medium-size-100 md-size-66" style="min-height: 500px">
                        <revenue-target-per-customer-type :targetRevenueChartData="targetRevenueChartData"/>
                    </div>
                </div>
                <div class="md-layout-item md-size-100">
                    <mini-grid-map :mini-grid-id="miniGridId"/>
                </div>
                <!--
                <div class="md-layout-item md-size-100">
                        TODO: Refactor this component later!
                        <target-list
                        ref="target"
                        :target-id="miniGridId"
                        target-type="mini-grid"
                        :base="highlighted.base"
                        :compared="highlighted.compared"
                    />
                </div>
                -->
                <div class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100">
                    <revenue-trends :trendChartData="trendChartData" :chartOptions="chartOptions"/>
                </div>

                <div class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100">
                    <tickets-overview :chart-options="chartOptions" :ticketData="openedTicketChartData"/>
                </div>
            </div>

            <transition name="modal" v-if="showModal">
                <div class="modal-mask">
                    <div class="modal-wrapper">
                        <div class="modal-container">
                            <md-card class="md-size-100">
                                <md-card-header>
                                    <h3>{{ $tc('words.edit') }} {{ miniGridData.name }}</h3>
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
            <md-dialog :md-active.sync="ModalVisibility">
                <md-dialog-content>
                    <stepper :watchingMiniGrids="watchingMiniGrids" :purchasingType="'logger'" v-if="ModalVisibility"/>
                </md-dialog-content>
            </md-dialog>
            <!-- purchasing modal-->
        </section>

    </div>

</template>

<script>

import moment from 'moment'
import { currency } from '@/mixins/currency'
import Datepicker from 'vuejs-datepicker'
import TargetList from './TargetList'
import EnergyChartBox from './EnergyChartBox'
import { MiniGridService } from '@/services/MiniGridService'
import Stepper from '../../shared/stepper'
import { EventBus } from '@/shared/eventbus'
import MiniGridMap from './MiniGridMap'
import BoxGroup from './BoxGroup'
import TicketsOverview from './TicketsOverview'
import RevenueTrends from './RevenueTrends'
import RevenuePerCustomerType from './RevenuePerCustomerType'
import RevenueTargetPerCustomerType from './RevenueTargetPerCustomerType'
import { BatchRevenueService } from '@/services/BatchRevenueService'
import i18n from '@/i18n'
import { notify } from '@/mixins/notify'

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
    mixins: [currency, notify],
    data () {
        return {
            miniGridService: new MiniGridService(),
            batchRevenueService: new BatchRevenueService(),
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
            miniGrids: [],
            chartOptions: {
                isStacked: true,
                chart: {
                    legend: {
                        position: 'top'
                    }
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
            loading: true,
            setPeriod: false,
            openedTicketChartData: [],
            trendChartData: {
                base: [],
                compare: [],
                overview: []
            },
            donutData: [],
            donutChartOptions: {
                pieHole: 1,
                legend: 'bottom',
                height: 500,
            },
            targetRevenueChartData: [],
            period: {
                from: null,
                to: null,
            },
            periodText: '-',
        }
    },
    created () {
        this.miniGridId = this.$route.params.id
        this.redirectionUrl += '/' + this.miniGridId
    },
    mounted () {
        this.getMiniGridData()
        EventBus.$on('closeModal', this.closeModal)
        this.miniGrids = this.miniGridService.getMiniGrids()
    },
    watch: {
        $route: function () {
            this.$router.go()
        },
    },
    methods: {
        async getMiniGridData () {
            this.loading = true
            EventBus.$emit('miniGridCachedDataLoading', this.loading)
            await this.$store.dispatch('miniGridDashboard/update')
            this.$store.dispatch('miniGridDashboard/get', this.miniGridId)
            this.miniGridData = this.$store.getters['miniGridDashboard/getMiniGridData']
            this.enableDataStream = this.miniGridData.data_stream === 1 ? true : false
            this.isLoggerActive = this.enableDataStream
            this.setDashboardData()
            this.loading = false
            EventBus.$emit('miniGridCachedDataLoading', this.loading)
        },
        async onPeriodChange () {
            let validator = await this.$validator.validateAll()
            if (!validator) {
                return
            }
            this.loading = true
            EventBus.$emit('miniGridCachedDataLoading', this.loading)
            const from = this.period.from !== null ? moment(this.period.from).format('YYYY-MM-DD') : null
            const to = this.period.to !== null ? moment(this.period.to).format('YYYY-MM-DD') : null
            if (from !== null) {
                this.periodText = from + ' - ' + to
            }
            await this.$store.dispatch('miniGridDashboard/updateByPeriod', { from, to })
            this.$store.dispatch('miniGridDashboard/get', this.miniGridId)
            this.miniGridData = this.$store.getters['miniGridDashboard/getMiniGridData']
            this.setDashboardData()
            this.setPeriod = false
            this.loading = false
            EventBus.$emit('miniGridCachedDataLoading', this.loading)
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
        setDashboardData () {
            this.miniGridData.revenueList.averages = this.calculateAverages(this.miniGridData.revenueList)
            this.donutData = this.batchRevenueService.initializeDonutCharts([this.$tc('words.connection'), this.$tc('words.revenue')], this.miniGridData)
            this.targetRevenueChartData = this.batchRevenueService.initializeColumnChart(this.miniGridData)
            this.setDonutChartOptions(this.donutData)
            this.fillTicketChart()
            this.fillRevenueTrendsOverView()
            this.fillRevenueTrends(this.tab)
        },
        checkToday () {
            if (moment().format('YYYY-MM-DD') === this.endDate) {
                return '(Today)'
            }
        },
        setMiniGrid (miniGridId) {
            this.$router.replace('/dashboards/mini-grid/' + miniGridId)
        },
        togglePeriod () {
            this.period = {
                from: null,
                to: null,
            }
            this.setPeriod = !this.setPeriod
        },
        setDonutChartOptions (donutData) {
            let value = donutData.reduce((acc, curr) => {
                if (curr[1] > 0) {
                    acc = true
                }
                return acc
            }, false)
            if (value) {
                this.donutChartOptions = {
                    pieHole: 1,
                    legend: 'bottom',
                    height: 500,
                }
            } else {
                this.donutData = []
                this.donutData.push([this.$tc('words.connection'), this.$tc('words.revenue')])
                this.donutData.push(['', { v: 1, f: this.$tc('phrases.noData') }])
                this.donutChartOptions.chartArea = {
                    left: '15%'
                }
                this.donutChartOptions.colors = ['transparent']
                this.donutChartOptions.pieSliceBorderColor = '#9e9e9e'
                this.donutChartOptions.pieSliceText = 'value'
                this.donutChartOptions.pieSliceTextStyle = {
                    color: '#9e9e9e'
                }
                this.donutChartOptions.tooltip = {
                    trigger: 'none'
                }

            }
        },
        calculateAverages (list) {
            let data = {}
            for (let connection in list.target.targets) {
                let result = '-'
                if (list.revenue[connection] > 0) {
                    result = parseInt(list.revenue[connection]) / list.totalConnections[connection]
                }
                data[connection] = result
            }
            return data
        },
        fillTicketChart () {
            let openedTicketChartData = []
            let closedTicketChartData = []

            openedTicketChartData.push([i18n.tc('words.period')])
            closedTicketChartData.push([i18n.tc('words.period')])
            for (let category in this.miniGridData.tickets.categories) {
                openedTicketChartData[0].push(this.miniGridData.tickets.categories[category].label_name)
                openedTicketChartData[0].push({ type: 'string', role: 'tooltip' })
                closedTicketChartData[0].push(this.miniGridData.tickets.categories[category].label_name)
                closedTicketChartData[0].push({ type: 'string', role: 'tooltip' })
            }

            for (let oT in this.miniGridData.tickets) {
                if (oT === 'categories') {
                    continue
                }
                let ticketCategoryData = this.miniGridData.tickets[oT]

                let ticketChartDataOpened = [oT]
                let ticketChartDataClosed = [oT]

                for (let tD in ticketCategoryData) {

                    let ticketData = ticketCategoryData[tD]
                    ticketChartDataOpened.push(ticketData.opened, oT + '\n' + [tD] + ' : ' + ticketData.opened + ' ' + i18n.tc('words.open', 2))
                    ticketChartDataClosed.push(ticketData.closed, oT + '\n' + [tD] + ' : ' + ticketData.closed + ' ' + i18n.tc('words.close', 2))

                }

                openedTicketChartData.push(ticketChartDataOpened)
                openedTicketChartData.push(ticketChartDataClosed)
                closedTicketChartData.push(ticketChartDataClosed)

            }

            this.openedTicketChartData = openedTicketChartData
        },
        fillRevenueTrendsOverView () {
            this.trendChartData.overview = [[i18n.tc('words.date')]]

            for (let dt in this.miniGridData.period) {
                for (let tariffNames in this.miniGridData.period[dt]) {
                    this.trendChartData.overview[0].push(tariffNames)
                }
                this.trendChartData.overview[0].push(i18n.tc('words.total'))
                break
            }
            for (let x in this.miniGridData.period) {
                let tmpChartData = [x]
                let totalRev = 0
                for (let d in this.miniGridData.period[x]) {
                    tmpChartData.push(this.miniGridData.period[x][d].revenue)
                    totalRev += this.miniGridData.period[x][d].revenue
                }
                tmpChartData.push(totalRev)
                this.trendChartData.overview.push(tmpChartData)
            }
            console.log(this.trendChartData)
            return this.trendChartData.overview
        },
        fillRevenueTrends (tab) {
            this.trendChartData.base = [[i18n.tc('words.date')]]
            this.trendChartData.compare = [[i18n.tc('words.date')]]

            for (let dt in this.miniGridData.period) {
                for (let tariffNames in this.miniGridData.period[dt]) {
                    this.trendChartData.base[0].push(tariffNames)
                    this.trendChartData.compare[0].push(tariffNames)
                }
                this.trendChartData.base[0].push(i18n.tc('words.total'))
                this.trendChartData.compare[0].push(i18n.tc('words.total'))
                if (tab !== 'weekly') {
                    break
                }
            }

            for (let x in this.miniGridData.period) {

                let tmpChartData = [x]
                let totalRev = 0
                for (let d in this.miniGridData.period[x]) {
                    tmpChartData.push(this.miniGridData.period[x][d].revenue)
                    totalRev += this.miniGridData.period[x][d].revenue
                }
                tmpChartData.push(totalRev)
                this.trendChartData.base.push(tmpChartData)
                this.trendChartData.base.splice(50)
            }
            return this.trendChartData.base

        },
        closeModal () {
            this.ModalVisibility = false
        },
        editMiniGrid () {
            this.showModal = true
        },
        calculateRevenuePercent (current, compared) {
            if (current + compared === 0) return -1
            return Math.round(current * 100 / compared)
        }
    }
}
</script>

<style>

.period-selector {
    position: absolute;
    top: 0;
    right: 0;
    z-index: 9999;
    padding: 15px;
    background-color: white;
    border: 1px solid #ccc;
    margin-right: 1rem;
    margin-top: 3rem
}

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
