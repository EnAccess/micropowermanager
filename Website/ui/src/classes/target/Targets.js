import { Paginator } from '../paginator'
import { resources } from '@/resources'
import { Target } from './Target'
import RepositoryFactory from '../../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class Targets {

    constructor () {
        this.list = []
        this.paginator = new Paginator(resources.target.list)
        this.repository = RepositoryFactory.get('target')

    }

    targetAtIndex (index) {
        return index >= this.list.length ? null : this.list[index]
    }

    async store (period, targetType, targetId, list) {
        let target = {
            period: period,
            targetType: targetType,
            targetId: targetId,
            data: list
        }
        try {
            let response = await this.repository.store(target)
            if(response.status === 201){
                return response
            }else{
                return new ErrorHandler(response.error, 'http', response.status)
            }
        }catch (e) {
            return new ErrorHandler(e, 'http')
        }

    }

    async updateList (data) {
        this.list = []

        for (let t in data) {
            let target = new Target()
            let owner = null

            target = target.fromJson(data[t])

            owner = data[t].owner_type

            this.list.push({
                'target': target,
                'owner': owner,
            })
        }
    }
}
