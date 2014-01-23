'use strict';

describe('Filter: depth', function () {

  // load the filter's module
  beforeEach(module('sumaAnalysis'));

  // initialize a new instance of the filter before each test
  var depth;

  beforeEach(inject(function ($filter) {
    depth = $filter('depth');
  }));

  it('should append mdash to title', function () {
    var objs = [],
        title = 'Title';

    for (var i = 1; i < 10; i += 1) {
      objs.push({
        title: 'Title',
        depth: i
      });
    }

    objs.forEach(function (obj, i) {
      title = '—' + title;
      expect(depth(obj)).to.equal(title);
    });
  });

  it('should handle items with name instead of title', function () {
    var input = {'name': 'No Activity', 'depth': 0,'percent': '100.00','count': 3707};
    expect(depth(input)).to.equal('No Activity');
  });
});
