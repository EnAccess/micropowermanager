import { Country } from '../Country'
import { Cluster } from '../clusters/Cluster'

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
