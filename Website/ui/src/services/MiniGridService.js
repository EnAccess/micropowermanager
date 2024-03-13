import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
import { convertObjectKeysToSnakeCase } from '@/Helpers/Utils'

export class MiniGridService {
    constructor() {
        this.repository = Repository.get('miniGrid')
        this.miniGrids = []
        this.miniGrid = {}
        this.currentTransaction = null
        this.soldEnergy = 0
        this.list = []
    }

    async getMiniGrids() {
        try {
            const { data, status, error } = await this.repository.list()
            if (status !== 200) return new ErrorHandler(error, 'http', status)
            this.miniGrids = data.data
            this.list = data.data
            return this.miniGrids
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async createMiniGrid(miniGridData) {
        try {
            const params = convertObjectKeysToSnakeCase(miniGridData)
            const { data, status, error } = await this.repository.create(params)
            if (status !== 200 && status !== 201)
                return new ErrorHandler(error, 'http', status)

            return data.data
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async getMiniGrid(miniGridId) {
        try {
            let response = await this.repository.get(miniGridId)

            if (response.status === 200 || response.status === 201) {
                this.miniGrid = response.data.data

                return this.miniGrid
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
    async getMiniGridGeoData(miniGridId) {
        try {
            const { data, status, error } =
                await this.repository.geoData(miniGridId)
            if (status !== 200) return new ErrorHandler(error, 'http', status)
            this.miniGrid = data.data
            return this.miniGrid
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async getMiniGridData(miniGridId) {
        try {
            let response = await this.repository.get(miniGridId)

            if (response.status === 200) {
                return response.data.data
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            return new ErrorHandler(e, 'http')
        }
    }

    async setMiniGridDataStream(miniGridId, dataStream) {
        try {
            let miniGridPM = {
                data_stream: dataStream,
            }
            let response = await this.repository.watch(miniGridId, miniGridPM)

            if (response.status === 200) {
                return response.data.data
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async getMiniGridDataStreams(dataStream) {
        try {
            let response = await this.repository.listDataStream(dataStream)

            if (response.status === 200) {
                this.miniGrids = response.data.data

                return this.miniGrids
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async getTransactionsOverview(miniGridId, startDate, endDate) {
        try {
            let period = {
                startDate: startDate,
                endDate: endDate,
            }
            let response = await this.repository.transactions(
                miniGridId,
                period,
            )

            if (response.status === 200) {
                this.currentTransaction = response.data.data

                return this.currentTransaction
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async getSoldEnergy(miniGridId, startDate, endDate) {
        try {
            let period = {
                startDate: startDate,
                endDate: endDate,
            }
            let response = await this.repository.soldEnergy(miniGridId, period)

            if (response.status === 200) {
                this.soldEnergy = response.data.data

                return this.soldEnergy
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}
