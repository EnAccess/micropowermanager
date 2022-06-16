import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
import { EventBus } from '@/shared/eventbus'

export class GenerationAssetsService {
    constructor () {
        this.repository = Repository.get('generationAssets')
        this.list=[]
        this.subscriber = null
        this.chartData = []
    }
    setSubscriber(subscriber) {
        this.subscriber= subscriber
    }
    async getList(miniGridId, startDate =null, endDate= null){
        let params = {}
        if(startDate !== null) {
            params['start_date'] = startDate
        }
        if (endDate !== null) {
            params['end_date'] = endDate
        }

        try {
            const response = await  this.repository.list(miniGridId, params)
            if(response.status === 200) {
                this.list = response.data.data
                return true
            }
        } catch(e){
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    prepareChartData(){
        let chartData = []
        //chart headers
        chartData.push(['Date', 'PV Power Output', 'Total Electrical Load Served', 'From Batteries','Generator'])

        this.list.map(reading => {
            chartData.push([
                new Date(Date.parse(reading['data_reading_date']+ ' '+ reading['data_reading_time'] )),
                {
                    v: reading.new_generated_energy,
                    f: `${reading.new_generated_energy} ${reading.new_generated_energy_unit}`,
                },
                {
                    v: reading.absorbed_energy_since_last,
                    f: `${reading.absorbed_energy_since_last} ${reading.absorbed_energy_since_last_unit}`,
                },
                {
                    v: reading.energyFromDieselGen,
                    f: `${reading.energyFromDieselGen} kWh`,
                },
                {
                    v: reading.d_newly_energy,
                    f: `${reading.d_newly_energy} ${reading.d_newly_energy_unit}`,
                },


            ])
        })
        this.chartData =  chartData
        EventBus.$emit('chartLoaded', 'energy')
        return chartData
    }
}
