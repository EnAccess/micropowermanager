export class Battery {
    constructor () {
        this.id = null
        this.mini_grid_id = null
        this.node_id = null
        this.device_id = null
        this.battery_count = null
        this.read_out = null

        this.soc_max = null
        this.soc_average = null
        this.soc_min = null
        this.soc_unit = null

        this.soh_max = null
        this.soh_average = null
        this.soh_min = null
        this.soh_unit = null

        this.d_total = null
        this.d_total_unit = null
        this.d_newly_energy = null
        this.d_newly_energy_unit = null
    }

    fromJson (data) {
        this.id = data['id']
        this.mini_grid_id = data['mini_grid_id']
        this.node_id = data['node_id']
        this.device_id = data['device_id']
        this.battery_count = data['battery_count']
        this.read_out = data['read_out']

        this.soc_max = data['soc_max']
        this.soc_average = data['soc_average']
        this.soc_min = data['soc_min']
        this.soc_unit = data['soc_unit']

        this.soh_max = data['soh_max']
        this.soh_average = data['soh_average']
        this.soh_min = data['soh_min']
        this.soh_unit = data['soh_unit']

        this.d_total = data['d_total']
        this.d_total_unit = data['d_total_unit']
        this.d_newly_energy = data['d_newly_energy']
        this.d_newly_energy_unit = data['d_newly_energy_unit']

        return this
    }

}
