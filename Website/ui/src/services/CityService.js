import Repository from '@/repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
import { convertObjectKeysToSnakeCase } from '@/Helpers/Utils'

import { Cluster } from '@/classes/clusters/Cluster'

export class Country {
    constructor() {}

    fromJson(jsonData) {
        this.id = jsonData.id
        this.name = jsonData.county_name
        this.countryCode = jsonData.country_code
    }
}

export class City {
    constructor() {}

    fromJson(jsonData) {
        this.id = jsonData.id
        this.name = jsonData.name
        this.clusterId = jsonData.cluster_id
        this.countryId = jsonData.country_id
        if ('country' in jsonData) {
            this.country = this.fetchCountry(jsonData.country)
        }
        if ('cluster' in jsonData) {
            this.fetchCluster(jsonData.cluster)
        }
        return this
    }

    fetchCountry(data) {
        let country = new Country()
        country.fromJson(data)
        return country
    }

    fetchCluster(data) {
        let cluster = new Cluster()
        cluster.fromJson(data)
        return cluster
    }

    getCities() {
        return axios
            .get(resources.city.list)
            .then((response) => {
                return response.data.data
            })
            .catch((err) => {
                return err
            })
    }
}

export class CityService {
    constructor() {
        this.cities = []
        this.city = {
            id: 0,
            name: '',
            cluster_id: 0,
            mini_grid_id: 0,
        }
        this.list = []
        this.repository = Repository.get('city')
    }

    async getCities() {
        try {
            const { data, status, error } = await this.repository.list()
            if (status !== 200) return new ErrorHandler(error, 'http', status)
            this.cities = data.data
            this.list = data.data

            return this.cities
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async createCity(cityData) {
        try {
            const params = convertObjectKeysToSnakeCase(cityData)
            const { data, status, error } = await this.repository.create(params)
            if (status !== 200 && status !== 201)
                return new ErrorHandler(error, 'http', status)
            this.city = data.data
            return this.city
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}
