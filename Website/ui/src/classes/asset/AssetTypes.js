import { Paginator } from '../paginator'
import { AssetType } from './AssetType'

export class AssetTypes {
    constructor() {
        this.list = []
        this.paginator = new Paginator(resources.assets.list)
    }

    async updateList(data) {
        this.list = []

        for (let a in data) {
            let assetType = new AssetType()
            assetType = assetType.fromJson(data[a])
            this.list.push(assetType)
        }
    }
}
