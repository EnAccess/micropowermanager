import { City } from '../Cities/city'

export class Cluster {
    constructor() {}

    fromJson(jsonData) {
        this.id = jsonData.id
        this.name = jsonData.name
        this.manager = jsonData.manager
        if ('cities' in jsonData) {
            this.cities = this.fetchCities()
        }
    }

    fetchCities(cities) {
        let result = []
        for (let i in cities) {
            let cityData = cities[i]
            let city = new City()
            city.fromJson(cityData)
            result.push(city)
        }
    }
}
