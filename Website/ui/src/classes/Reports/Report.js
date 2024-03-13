export class Report {
    constructor() {
        this.id = null
        this.name = null
        this.path = null
        this.date = null
        this.type = null
    }

    fromJson(data) {
        this.id = data.id
        this.name = data.name
        this.path = data.path
        this.date = data.date
        this.type = data.type
        return this
    }
}
