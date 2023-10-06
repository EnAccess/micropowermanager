import RepositoryFactory from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
import i18n from '../i18n'

export class ClusterService {
    constructor () {
        this.repository = RepositoryFactory.get('cluster')
        this.clusters = []
        this.financialData = []
        this.clusterTrends = []
        this.trendChartData = { base: null, overview: null }
    }

    async createCluster (geoType, location, name, managerId) {
        const cluster_PM = {
            geo_type: geoType,
            geo_data: location,
            name: name,
            manager_id: managerId,
        }
        try {
            const response = await this.repository.create(cluster_PM)
            return this.responseValidator(response, [200, 201])
        } catch (e) {
            return new ErrorHandler(e.response.data.data.message, 'http')
        }
    }

    async getClusters () {
        try {
            const response = await this.repository.list()
            return this.responseValidator(response)
        } catch (e) {
            return new ErrorHandler(e.response.data.data.message, 'http')
        }
    }

    async getClusterGeoLocation (clusterId) {
        try {
            const response = await this.repository.getGeoLocation(clusterId)
            return this.responseValidator(response)
        } catch (e) {
            return new ErrorHandler(e.response.data.data.message, 'http')
        }
    }

    async getDetails (clusterId) {
        try {
            const response = await this.repository.get(clusterId)
            return this.responseValidator(response)
        } catch (e) {
            return new ErrorHandler(e.response.data.data.message, 'http')
        }
    }

    async getClusterRevenues (clusterId) {
        try {
            const response = await this.repository.getClusterRevenues(clusterId)
            return this.responseValidator(response)
        } catch (e) {
            return new ErrorHandler(e.response.data.data.message, 'http')
        }
    }

    async getAllRevenues (period, startDate, endDate) {
        const queryString = `?period=${period}&startDate=${startDate ??
        ''}&endDate=${endDate ?? ''}`
        try {
            const response = await this.repository.getAllRevenues(queryString)
            this.financialData = this.responseValidator(response, [200, 201])
            return this.financialData
        } catch (e) {
            return new ErrorHandler(e.response.data.data.message, 'http')
        }
    }

    fillTrends () {
        let trendKeys = (Object.keys(this.clusterTrends))
        this.trendChartData.base = [(Object.keys(this.clusterTrends))]
        this.trendChartData.base[0].unshift('Date')
        for (let i in this.clusterTrends[trendKeys[0]]) { // iterate over periods
            let tmpData = []
            for (let j in trendKeys) { //iterate over connection names
                tmpData.push(
                    this.clusterTrends[trendKeys[j]][i]
                )
            }
            tmpData.unshift(i)
            this.trendChartData.base.push(tmpData)
        }
    }

    insertCityNames (count, data) {
        for (let i = 0; i < count; i++) {
            data.push(this.financialData[i].name)
        }
        return data
    }

    lineChartData (summary) {
        let data = []
        data.push([i18n.tc('words.period')])

        let itemCount = this.financialData.length
        if (itemCount === 0) {
            return
        }

        data[0] = this.insertCityNames(itemCount, data[0])
        if (summary) {
            data[0].push(i18n.tc('words.total'))
        }

        let periods = this.financialData[0].period
        for (let p in periods) {
            data.push(this.getPeriodicData(itemCount, p, summary))
        }
        return data
    }

    getPeriodicData (count, periodName, summary) {
        let data = []
        let sum = 0
        data.push(periodName)
        for (let i = 0; i < count; i++) {
            if (summary) {
                sum += this.financialData[i].period[periodName].revenue
            }
            data.push(this.financialData[i].period[periodName].revenue)
        }
        if (summary) {
            data.push(sum)
        }
        return data
    }

    /**
     * Generates data array for column and donut chart
     */
    columnChartData (summary, type) {
        let data = []
        let summaryRevenue = 0
        let infoData = type === 'cluster' ? i18n.tc('words.cluster') : i18n.tc('words.miniGrid')
        data.push([infoData, i18n.tc('words.revenue')])
        for (let i in this.financialData) {
            let cD = this.financialData[i]
            if (summary) {
                summaryRevenue += cD.totalRevenue
            }
            data.push([cD.name, cD.totalRevenue])
        }
        if (summary) {
            data.push(['Sum', summaryRevenue])
        }
        return data
    }

    responseValidator (response, expectedStatus = [200]) {
        return expectedStatus.includes(response.status)
            ? response.data.data :
            new ErrorHandler(response.error, 'http', response.status)
    }
}
