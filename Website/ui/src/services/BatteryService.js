import { Battery } from '@/entities/Battery'
import { EventBus } from '@/shared/eventbus'
import Client from '../repositories/Client/AxiosClient'

export class BatteryService {
    constructor () {
        this.batteryData = []
        this.stateChartData = []
        this.energyChartData = []
        this.subscriber = null

    }

    async getBatteryUsageList (
        miniGridId, withChartData = false, startDate = null, endDate = null) {
        if (typeof (miniGridId) === 'undefined') {
            return null
        }
        let params = {}
        if (startDate) {
            params['start_date'] = startDate
        }
        if (endDate) {
            params['end_date'] = endDate
        }
        let list = await Client.get(
            `${resources.batteries.detail}${miniGridId}/batteries`,
            { params: params },
        )

        list.data.data.map((battery) => (
            this.fetchBatteryData(battery, withChartData)
        ))
        if (withChartData) {
            EventBus.$emit('chartLoaded', this.subscriber)
        }

        return true
    }

    fetchBatteryData (battery, withCartData) {
        this.batteryData.push(new Battery().fromJson(battery))
        if (withCartData) {
            this.chartDataDistributor(battery)
        }

    }

    chartDataDistributor (data) {

        this.prepareStateChartData(data)

    }

    prepareStateChartData (batteryData) {
        if (this.stateChartData.length === 0) {
            this.stateChartData.push(['Date', 'SoC'])
        }
        let chartData = []
        chartData.push(
            new Date(Date.parse(batteryData.read_out)),
            {
                v: batteryData.soc_average,
                f: `${batteryData.soc_average}%`,
            },
        )
        this.stateChartData.push(chartData)
    }

    async prepareChartData () {
        if (this.batteryData.length === 0) {
            return null
        }

        this.batteryData.map((battery) => (
            this.chartDataDistributor(battery)
        ))
        EventBus.$emit('chartLoaded', this.subscriber)

    }

}
