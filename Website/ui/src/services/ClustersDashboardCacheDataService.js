import RepositoryFactory from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class ClustersDashboardCacheDataService{
    constructor () {
        this.repository = RepositoryFactory.get('clustersDashboardCacheData')
    }

    async list () {
        try {
            const response = await this.repository.list()
            return this.responseValidator(response)
        } catch (e) {
            return new ErrorHandler(e.response.data.data.message, 'http')
        }
    }

    async update (){
        try {
            const response = await this.repository.update()
            return this.responseValidator(response)
        }catch (e) {
            return new ErrorHandler(e.response.data.data.message, 'http')
        }
    }

    async detail (id) {
        try {
            const response = await this.repository.detail(id)
            return this.responseValidator(response)
        } catch (e) {
            return new ErrorHandler(e.response.data.data.message, 'http')
        }
    }

    responseValidator (response, expectedStatus = [200]) {
        return expectedStatus.includes(response.status)
            ? response.data.data :
            new ErrorHandler(response.error, 'http', response.status)
    }
}
