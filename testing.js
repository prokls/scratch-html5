//
// HIGHLY EXPERIMENTAL. There might be dragons!
//

$(function () {
	var event_bricks = [
		"whenGreenFlag", "whenIReceive", "whenCloned", "whenClicked",
		"whenKeyPressed", "whenSceneStarts", "whenSensorGreaterThan"
	];

	// assert
	var assert = function (condition, sprite, event, msg) {
		if (!condition)
			alert(msg);
	};

	//-------------------------- brick testing --------------------------------
	var testGoBackByLayers = function (layer) {
		console.log("TODO: Test go back by layers (", layer, ")");
	};

	var testGoToXY = function (x, y) {
		console.log("TODO: Test GOTO with (", x, y, ")");
	};

	var evaluate_expression = function (structure) {
		console.log("TODO: evaluate value of structure " + structure);
		return 42;
	};

	var brick_tests = {
		"goBackByLayers:" : testGoBackByLayers,
		"gotoX:y:" : testGoToXY
	};

	//----------------------------- framework ---------------------------------

	var request = function (project_id) {
		var url = "http://localhost/scratch-html5/resource.php"
			+ "?resource=internalapi/project/" + escape(project_id) + "/get/";
		var result;
		$.ajax({url: url, async: false, dataType: "json", success: function (data) {
			result = data;
		}});
		return result;
	};

	var is_event_brick = function (brick_name) {
		// other approach: membership test to event_bricks
		if ((new RegExp(/^when\w+/)).exec(brick_name))
			return true;
		else
			return false;
	};

	var get_events = function (script) {
		var sprites = script.children;
		var events = {};
		for (var sprite_id in sprites) {
			var objname = sprites[sprite_id]["objName"];

			for (var block_id in sprites[sprite_id].scripts) {
				var brickname = sprites[sprite_id]["scripts"][block_id][2][0][0];

				if (is_event_brick(brickname))
					if (objname in events)
						events[objname].push(brickname);
					else
						events[objname] = [brickname];
			}
		}
		return events;
	};

	var get_control_paths = function (script, sprite, eventbrick) {
		var control_paths = [];
		var sprites = script.children;
		for (var sprite_id in sprites) {
			var objname = sprites[sprite_id]["objName"];
			if (objname !== sprite)
				continue;

			for (var block_id in sprites[sprite_id].scripts) {
				var brickname = sprites[sprite_id]["scripts"][block_id][2][0][0];

				if (brickname !== eventbrick)
					continue;

				control_paths.push([sprite].concat(sprites[sprite_id]["scripts"][block_id][2]));
			}
		}
		return control_paths;
	};

	var get_object = function (sprite) {
		// return actual DOM element
	};

	var run_control_path = function (script, control_path) {
		var sprite = control_path[0];
		control_path.slice(1).forEach(function (brick) {
			var brickname = brick[0];
			var args = brick.slice(1);

			// if args contains some object, evaluate value of object first
			args = args.map(function (v) {
				if (typeof v === "object")
					return evaluate_expression(v);
				else
					return v;
			});

			if (brickname in brick_tests)
				brick_tests[brickname].apply(brick_tests[brickname], args);
			else
				console.info("Unsupported brick: " + brickname);
		});
	};

	//------------------------------ JS events --------------------------------

	/*$(document).ready(function () {
		var json = request("11854933");
		var events = get_events(json);
		for (var objname in events) {
			$("#events").append($("<li></li>").text(objname + ": " + events[objname]));
		}
		for (var objname in events) {
			for (var ev_id in events[objname]) {
				get_control_paths(json, objname, events[objname][ev_id]).forEach(function (control_path) {
					run_control_path(json, control_path);
				});
			}
		}
		
	});*/

	$(document).ready(function () {
		Runtime.prototype.loadStart = function () {
			console.log(io.data);
		};


		var old_functions_table = {};
		for (var brick in interp.primitiveTable) {
			if (!(brick in old_functions_table)) {
				old_functions_table[brick] = interp.primitiveTable[brick];
			}

			interp.primitiveTable[brick] = function (b) {
				console.log("Run brick '" + brick + "'.");
				old_functions_table[brick].apply(this, [b]);
			}
		}
	});
});
