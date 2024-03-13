export class PV {
    constructor() {
        this.id = null
        this.mini_grid_id = null
        this.node_id = null
        this.device_id = null
        this.daily = null
        this.daily_unit = null
        this.total = null
        this.total_unit = null
        this.new_generated_energy = null
        this.new_generated_energy_unit = null
        this.created_at = null
        this.updated_at = null
        this.reading_date = null
    }

    fromJson(data) {
        this.id = data['id']
        this.mini_grid_id = data['mini_grid_id']
        this.node_id = data['node_id']
        this.device_id = data['device_id']
        this.daily = data['daily']
        this.daily_unit = data['daily_unit']
        this.total = data['total']
        this.total_unit = data['total_unit']
        this.new_generated_energy = data['new_generated_energy']
        this.new_generated_energy_unit = data['new_generated_energy_unit']
        this.created_at = data['created_at']
        this.updated_at = data['updated_at']
        this.reading_date = data['reading_date']

        return this
    }
}
