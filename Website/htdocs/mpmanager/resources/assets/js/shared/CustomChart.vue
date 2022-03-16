<template>
    <div>
        <md-card >
            <md-card-content>
                <div v-if="show > 0 " style="min-height: 300px">
                    <div v-if="show === 1"
                         style="position: absolute; width: 100%; min-height: 300px; background-color: white; color: #0a0a0c; top: 0; left: 0; z-index:999;">
                        <div class="prepare-data">

                            <h4>{{ $tc('phrases.preparingChartData') }}</h4>
                        </div>
                    </div>
                    <div id="dashboard" ref="dash">
                        <div id="chart_div" ref="chart"></div>
                        <div id="control_div" ref="control"></div>
                    </div>
                </div>
                <div v-else>
                    <div align="center">
                        <h3>{{ $tc('phrases.loadingChartData') }}</h3>
                        <img width="200px" align="center" src="../../spinner/spinner.gif" alt="">
                    </div>
                </div>
            </md-card-content>
        </md-card>

    </div>
</template>
<script>

import { EventBus } from './eventbus'
google.charts.load('current', {'packages':['corechart', 'controls']})
export default {
    name: 'CustomChart',
    props: {
        subscriber: {
            required: true,
            type: String,
        },
        chartType: {
            type: String,
            default: 'line',
            required: true
        },
        data: {
            type: [Array, Object],
        },
        title: {
            type: String,
            default: '',
            required: false,
        },
        legend: {
            type: String,
            default: 'right',
            required: false,
        },

    },
    data () {
        return {
            show: 0,
            options: {
                title: this.title,
                interpolateNulls: false,
                //curveType: 'none',
                legend: { position: this.legend },
                hAxis: {
                    textStyle: {
                        fontSize: 12,
                    },
                    gridlines: {
                        count: -1,
                        units: {
                            days: { format: ['MMM dd'] },
                            hours: { format: ['HH:mm'] },
                        }
                    },
                    minorGridlines: {
                        units: {
                            hours: { format: ['hh:mm:ss a', 'ha'] },
                            minutes: { format: ['HH:mm a Z',] }
                        }
                    }
                },
                ui: {
                    chartOptions: {
                        width: '50%',
                        chartArea: {
                            width: '60%',
                        },
                    },
                },

            },
            control: {
                filterColumnLabel: this.$tc('words.date'),
                ui: {
                    chartOptions: {
                        height: 50,
                        width: '100%',
                        fontSize: 10
                    },
                },
                hAxis: {
                    gridlines: {
                        count: -1,
                        units: {
                            days: { format: ['MMM dd'] },
                            hours: { format: ['HH:mm', 'ha'] },
                        }
                    },
                    minorGridlines: {
                        units: {
                            hours: { format: ['hh:mm:ss a'] },
                            minutes: { format: ['HH:mm a Z'] }
                        }
                    }
                }
            }

        }
    },

    mounted () {
        EventBus.$on('chartLoaded',this.chartLoaded)

    },
    methods: {
        chartDataReady(){
            google.visualization.events.removeListener(this.chartEvent)
            this.show = 2
        },
        drawChart (type) {
            switch (type) {
            case 'line':
                this.drawLineChart()
                break
            case 'bar':
                this.drawBarChart()
                break
            case 'pie':
                this.drawPieChart()
                break
            }
        },
        chartLoaded(subscriber){
            if(this.subscriber === subscriber){
                google.charts.setOnLoadCallback(() => this.drawChart(this.chartType))
                this.show = 1
            }
        },

        drawLineChart () {
            if (this.data !== null || this.data !== undefined) {
                let dash = new google.visualization.Dashboard(this.$refs.dash)
                let data = google.visualization.arrayToDataTable(this.data)
                this.chartEvent = google.visualization.events.addListener(dash, 'ready', this.chartDataReady)
                let wrapper = new google.visualization.ChartWrapper({
                    chartType: 'LineChart',
                    dataTable: data,
                    options: this.options,
                    containerId: this.$refs.chart
                })
                let control = new google.visualization.ControlWrapper({
                    controlType: 'ChartRangeFilter',
                    containerId: this.$refs.control,
                    options: this.control
                })

                dash.bind([control], [wrapper])
                dash.draw(data)


            }

        },
        drawBarChart () {
            let data = new google.visualization.DataTable()
            data.addColumn('date', this.$tc('words.date'))
            data.addColumn('number', this.$tc('words.generate',2))
            data.addColumn('number', this.$tc('words.sell',2))
            data.addRows([
                [new Date(2020, 7, 10), 3, 2.25],
                [new Date(2020, 7, 11), 5, 3.5],
                [new Date(2020, 7, 12), 2, 1],
                [new Date(2020, 7, 13), 4, 2.25],
                [new Date(2020, 7, 14), 3, 2.25],
                [new Date(2020, 7, 15), 6, 3],
                [new Date(2020, 7, 16), 9, 4],
                [new Date(2020, 7, 17), 8, 5.25],
                [new Date(2020, 7, 18), 8, 7.5],
                [new Date(2020, 7, 19), 7, 5],
            ])

            let options = {
                title: 'Generated and Sold Level of Energy',
                hAxis: {
                    format: 'MMM dd',
                },
            }

            var chart = new google.visualization.ColumnChart(this.$refs.chart)
            this.chartDataReady()
            chart.draw(data, options)

        },
        drawPieChart () {
        //TODO: add a new method to draw pie chart
        },

    }

}
</script>

<style scoped>
    .prepare-data {
        position: relative;
        top: 100px;
        left: 45%;

    }

    .prepare-data > h4 {
        color: #0c5460;
    }

</style>
