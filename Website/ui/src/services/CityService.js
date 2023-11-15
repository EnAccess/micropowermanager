import Repository from '@/repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
import { convertObjectKeysToSnakeCase } from '@/Helpers/Utils'

export class CityService {
    constructor () {
        this.repository = Repository.get('city')
        this.cities = []
        this.city = {
            id: 0,
            name: '',
            cluster_id: 0,
            mini_grid_id: 0,
        }
    }

    async getCities () {
        try {
            const { data, status, error } = await this.repository.list()
            if (status !== 200) return new ErrorHandler(error, 'http', status)
            this.cities = data.data
            return this.cities
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async createCity (cityData) {
        try {
            const params = convertObjectKeysToSnakeCase(cityData)
            const { data, status, error } = await this.repository.create(params)
            if (status !== 200 && status !== 201) return new ErrorHandler(error, 'http', status)
            this.city = data.data
            return this.city

        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }

    }

}
