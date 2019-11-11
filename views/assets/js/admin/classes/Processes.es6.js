import Constants from '../../Constants.es6'

class Processes {

    static get(id) {
        let processes = _store.get(Constants.PROCESS)

        processes.forEach(process => {
            if ( id === process.id ) {
                return process
            }
        })
    }

    static set(new_process_data) {
        let processes = _store.get(Constants.PROCESS)

        processes.forEach((process, i) => {
            if ( new_process_data.id === process.id ) {
                processes[i] = new_process_data
            }
        })
        _store.set(Constants.PROCESS, processes)
    }

    static create(init) {
        let processes = _store.get(Constants.PROCESS)

        if ('undefined' === typeof processes ) {
            processes = [];
        }

        processes.push(init)

        _store.set(Constants.PROCESS, processes)
    }

    static add(id, data) {
        let processes = _store.get(Constants.PROCESS)

        processes.forEach((process, i) => {
            if ( id === process.id ) {
                processes[i].push(data);
            }
        })
        _store.set(Constants.PROCESS, processes)
    }

    static delete(id) {
        let processes = _store.get(Constants.PROCESS)

        processes.forEach((process, i) => {
            if ( id === process.id ) {
                // processes[i].splice(0, 1) // not tested!
                // _store.set(Constants.PROCESS, processes)
            }
        })
    }


}

export default Processes