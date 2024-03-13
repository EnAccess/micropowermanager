export class Country {
    constructor() {}

    fromJson(jsonData) {
        this.id = jsonData.id
        this.name = jsonData.county_name
        this.countryCode = jsonData.country_code
    }
}
