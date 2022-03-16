import {EventBus} from '../../shared/eventbus'

export class AssetType {
    constructor () {
        this.id = null
        this.name = null
        this.updated_at = null
        this.edit = false
    }


    fromJson(data) {
        this.id = data.id
        this.name = data.name
        this.updated_at = data.updated_at
        return this
    }

    newAsset(){
        return axios.post(resources.assets.type.store, {
            'asset_type_name': this.name
        }).then((response) => {
            this.id = response.data.data.id
            this.name = response.data.data.name
            this.updated_at = response.data.data.updated_at
            EventBus.$emit('assetTypeAdded', this)
        })
    }

    update(){
        return axios.put(resources.assets.type.update+ '/'+this.id, {'asset_type_name': this.name})
            .then((response) => {
                return response
            })
    }
    delete(){
        return axios.delete(resources.assets.type.delete+ '/'+this.id)
            .then((response) => {
                return response
            })
    }
}
