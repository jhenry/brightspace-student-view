<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student View Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
</head>

<body>
    <div class="container">
        <div class="row pt-4 mx-1">
            <div class="col-8 mx-5">
                <h1>Student View Account</h1>
                @if(Session::has('alert'))
                <p class="alert {{ Session::get('alert-class', 'alert-info') }}">{{ Session::get('alert') }}</p>
                @endif

                @if($isAllowed)
                    @if(!$svExists)
                    <form action="{{ url('/add') }}" method="POST">
                        <input type="hidden" name="orgUnitId" value="" id="orgUnitId">
                        <p>Add a student view account to this course.</p>
                        <button type="submit" name="action-add" value="add" class="btn btn-primary">Add Student View Account</button>
                        {{ csrf_field() }}
                    </form>
                    @else
                    <form action="{{ url('/remove') }}" method="POST">
                        <input type="hidden" name="orgUnitId" value="" id="orgUnitId">
                        <p>A student view account exists for this course. You can enter student view by <a target="_parent" href="{{ $classlistUrl }}">going to the classlist</a> and impersonating the user.</p>
                        <button type="submit" name="action-remove" value="remove" class="btn btn-primary">Delete Student View Account</button>
                        {{ csrf_field() }}
                    </form>
                    @endif
                @else
                <p class="alert alert-danger">You are not authorized to use this tool.</p>
                @endif
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>

</html>
