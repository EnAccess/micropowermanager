<template>
    <div>
        <div class="md-layout" style="display: grid;">
            <md-card class="card-list-item-content">
                <md-card-header class="sdfsd" style="background: rgb(168, 208, 220)">
                    <chartist
                        class="chart"
                        ratio="ct-chart"
                        :type="type"
                        :event-handlers="events"
                        :data="data"
                        :options="options"

                    >
                    </chartist>
                </md-card-header>
                <md-card-content>

                    <div>
                        <p>Veri ile ilgili not</p>


                    </div>

                </md-card-content>
            </md-card>
        </div>
    </div>
</template>

<script>
import moment from 'moment'
export default {
    name: 'ChartistBox',
    props: {
        data: {
            type: Object,
            default: () => ({})
        },
        type: {
            type: String,
            default: 'Line'
        },
        options: {
            type: Object,
            default: () => ({
                seriesBarDistance: 15,
                showArea: true,
                showPoint: true,
                fullWidth: true
            })
        }

    },
    data () {
        return {

            distributedSeriesData: {
                labels: ['Q1', 'Q2', 'Q3', 'Q4'],
                series: [15, 40, 60, 100]
            },
            distributedSeriesOptions: {
                distributeSeries: true,
            },
            timeLineSeriesData: {
                series: [{
                    color: 'blue',
                    stroke: 'blue',
                    name: 'series-1',
                    data: [
                        { x: new Date(143134652600), y: 53 },
                        { x: new Date(143234652600), y: 40 },
                        { x: new Date(143340052600), y: 45 },
                        { x: new Date(143366652600), y: 40 },
                        { x: new Date(143410652600), y: 20 },
                        { x: new Date(143508652600), y: 32 },
                        { x: new Date(143569652600), y: 18 },
                        { x: new Date(143579652600), y: 11 }
                    ]
                }, {
                    name: 'series-2',
                    data: [
                        { x: new Date(143134652600), y: 53 },
                        { x: new Date(143234652600), y: 35 },
                        { x: new Date(143334652600), y: 30 },
                        { x: new Date(143384652600), y: 30 },
                        { x: new Date(143568652600), y: 10 }
                    ]
                }]
            },
            timeLineOptions: {
                axisX: {
                    type: this.$chartist.FixedScaleAxis,
                    divisor: 5,
                    labelInterpolationFnc (value) {
                        return moment(value).format('MMM D')
                    }
                }
            },
            withOnlySeries: {
                series: [[
                    { x: 1, y: 100 },
                    { x: 2, y: 50 },
                    { x: 3, y: 25 },
                    { x: 5, y: 12.5 },
                    { x: 8, y: 6.25 }
                ]]
            },
            withOnlySeriesOptions: {
                axisX: {
                    type: this.$chartist.AutoScaleAxis,
                    onlyInteger: true
                }
            },
            events: [{
                event: 'draw',
                fn: context => {
                    context.element.attr({
                        style: `stroke: hsl(${Math.floor(this.$chartist.getMultiValue(context.value) / 100 * 100)}, 60%, 50%);`
                    })
                }
            }]
        }
    },

}
</script>

<style>
    .ct-series-a .ct-line,
    .ct-series-a .ct-point {
        stroke: blue;
    }

    .ct-series-b .ct-line,
    .ct-series-b .ct-point {
        stroke: green;
    }
</style>

