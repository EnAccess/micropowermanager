import { City } from '@/services/CityService'
import { SubTarget } from './SubTarget'

export class Target {
    constructor() {
        this.id = null
        this.startDate = null
        this.endDate = null
        this.subTargets = []
        this.city = new City()
    }

    fromJson(jsonData) {
        this.id = jsonData.id
        this.targetDate = jsonData.target_date
        this.type = jsonData.type
        this.owner = jsonData.owner

        if ('sub_targets' in jsonData) {
            for (let i = 0; i < jsonData.sub_targets.length; i++) {
                let subTarget = new SubTarget()
                this.subTargets.push(
                    subTarget.fromJson(jsonData.sub_targets[i]),
                )
            }
        }
        return this
    }
}
